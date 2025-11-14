<?php

namespace Echoyl\Sa\Services\export;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Vtiful\Kernel\Excel;
use Vtiful\Kernel\Format;

class Vtiful
{
    public $excel;

    public $handle;

    public $column_length = 1;

    public $data_length = 0;

    public $folder = 'export';

    public $config;

    public $has_top = false;

    public $storage_path = 'app/public/';

    public function __construct($config = [])
    {
        $vconfig = [
            'path' => storage_path($this->storage_path.$this->folder), // xlsx文件保存路径
        ];
        $excel = new Excel($vconfig);
        if (! file_exists($vconfig['path'])) {
            mkdir($vconfig['path']);
        }
        $this->config = $config;
        $this->excel = $excel;
        if (isset($config['filename'])) {
            $this->excel = $this->excel->fileName($config['filename'], 'sheet1');
            $this->handle = $excel->getHandle();
        }

        $this->column_length = $this->getColumnLength();
    }

    /**
     * 获取列的长度
     *
     * @return int
     */
    public function getColumnLength()
    {
        $columns = $this->getColumns();

        return count($columns);
    }

    /**
     * 表的最顶部显示
     *
     * @return \Echoyl\Sa\Services\export\Vtiful
     */
    public function top()
    {
        $top = Arr::get($this->config, 'top');
        if (! $top) {
            return $this;
        }

        $this->has_top = true;

        $content = Arr::get($top, 'content');

        $excel = $this->excel;

        $height = Arr::get($top, 'height');
        if ($height) {
            $excel->setRow('A1', $height);
        }

        $style = $this->getStyle($top);

        if ($this->column_length > 1) {
            $start = Excel::stringFromColumnIndex(0).'1';
            $end = Excel::stringFromColumnIndex($this->column_length - 1).'1';
            $excel->mergeCells(implode(':', [$start, $end]), $content, $style);
        } else {
            $excel->insertText(0, 0, $content, '', $style);
        }

        $this->excel = $excel;

        return $this;
    }

    public function getHead()
    {
        return Arr::get($this->config, 'head');
    }

    public function getColumns()
    {
        $head = $this->getHead();

        $columns = Arr::get($head, 'columns', []);

        return $columns;
    }

    /**
     * 表头显示
     *
     * @return \Echoyl\Sa\Services\export\Vtiful
     */
    public function head()
    {
        $head = $this->getHead();

        $columns = $this->getColumns();

        $head_row_number = $this->has_top ? 1 : 0;

        $height = Arr::get($head, 'height');

        if ($height) {
            $this->excel->setRow('A'.($head_row_number + 1), $height);
        }

        foreach ($columns as $key => $column) {
            // 获取列单独的样式
            $setting = Arr::get($column, 'setting', []);
            $style = $this->getStyle(array_merge($head, $setting));
            $this->excel->insertText($head_row_number, $key, $column['title'], '', $style);
            $width = Arr::get($column, 'width', 20); // 默认给一个宽度
            $column_name = Excel::stringFromColumnIndex($key);
            $this->excel->setColumn(implode(':', [$column_name, $column_name]), $width);
            // summary 检测
            $this->summary($key, $column);

        }

        return $this;
    }

    /**
     * 根据设置获取样式信息
     *
     * @param [type] $set
     * @return void
     */
    public function getStyle($set)
    {
        $background = Arr::get($set, 'background');
        $border = Arr::get($set, 'border');
        $color = Arr::get($set, 'color');
        $fontsize = Arr::get($set, 'fontsize');
        $bold = Arr::get($set, 'bold');
        $fileHandle = $this->handle;
        $format = new Format($fileHandle);
        $style = null;
        if ($background || $color || $fontsize || $border) {
            if ($background) {
                $format->background($this->getColor($background));
            }
            if ($color) {
                $format->fontColor($this->getColor($color));
            }
            if ($fontsize) {
                $format->fontSize($fontsize);
            }
            if ($border) {
                // 边框默认使用薄边框风格
                $format->border(Format::BORDER_THIN);
            }
            if ($bold) {
                $format->bold();
            }
            $style = $this->getFormat($format);
        }

        return $style;
    }

    public function getFormat($format = false)
    {
        if (! $format) {
            $format = new Format($this->handle);
        }

        return $format->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)->toResource();
    }

    /**
     * 通过rgb获取16位整型的颜色值
     *
     * @param [type] $color
     * @return int
     */
    public function getColor($color)
    {
        // 去除前导的井号(#)
        $color = ltrim($color, '#');

        // 将16进制颜色值分割为红、绿、蓝三部分
        $parts = str_split($color, 2);

        // 将16进制颜色值转换为10进制整数
        $r = hexdec($parts[0]);
        $g = hexdec($parts[1]);
        $b = hexdec($parts[2]);

        // 将10进制整数拼接为一个整数值
        $intValue = ($r << 16) | ($g << 8) | $b;

        return $intValue;
    }

    /**
     * 导出列表数据
     *
     * @param [type] $data
     * @return void
     */
    public function export($data, $merges = [])
    {

        $this->data_length = count($data);

        $data_style = Arr::get($this->config, 'data');

        $row_height = Arr::get($data_style, 'height');

        $default_style = $this->getStyle($data_style);

        if ($default_style) {
            $this->excel->defaultFormat($default_style);
        }

        $this->top()->head();

        $data_row_number = $this->has_top ? 2 : 1;

        if ($row_height) {
            // 设置行高
            $all_row = implode(':', ['A'.($data_row_number + 1), 'A'.($data_row_number + $this->data_length)]);
            $this->excel->setRow($all_row, $row_height, $this->getFormat());
        }

        $this->excel->setCurrentLine($data_row_number)->data($data);

        // 合并设置
        foreach ($merges as $merge) {
            [$col,$start,$end,$val] = $merge;
            $row_str = implode(':', [$col.($data_row_number + $start + 1), $col.($data_row_number + 1 + $end)]);
            $this->excel->mergeCells($row_str, $val);
        }

        $this->excel->output();

        $filename = $this->config['filename'];
        $ret = ['url' => tomedia($this->folder.'/'.$filename), 'download' => $filename];

        return $ret;
    }

    public function summary($key, $column)
    {

        $setting = Arr::get($column, 'setting', []);
        $sum = Arr::get($setting, 'sum');
        if (! $sum) {
            return;
        }
        // 开启合计插入公式
        $row = $this->has_top ? $this->data_length + 2 : $this->data_length + 1;
        $start_row = $this->has_top ? 3 : 2;
        $column_name = Excel::stringFromColumnIndex($key);
        $sum_col = implode(':', [$column_name.$start_row, $column_name.($start_row + $this->data_length - 1)]);
        // d($sum_col,$row,$key);
        $this->excel->insertFormula($row, $key, '=SUM('.$sum_col.')', $this->getFormat());

    }

    /**
     * 通过UploadedFile 获取excel数据
     *
     * @param [\Illuminate\Http\UploadedFile] $file
     * @return void
     */
    public function getSheetData($file)
    {

        $file_path = $file->store($this->folder);

        $filename = str_replace($this->folder.'/', '', $file_path);

        // d($filename);

        $data = $this->excel->openFile($filename)->openSheet()->getSheetData();

        $this->excel->close();
        // 读取后将文件删除
        Storage::delete($file_path);

        return $data;
    }
}
