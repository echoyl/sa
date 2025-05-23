<?php

namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Facades\DB;

class Dump
{
    var $content = '';
    var $crlf = "\n";
    var $max_sql_size = 50;

    public function emptyLine()
    {
        $this->content .= $this->crlf;
    }

    public function addLine($line)
    {
        $this->content .= $line . $this->crlf;
    }

    public function exportStructure($table_name, $check = [])
    {
        $crlf = $this->crlf;


        // $db = env('DB_DATABASE');
        if (in_array('drop', $check)) {
            $this->addLine("DROP TABLE IF EXISTS {$table_name};");
            $this->emptyLine();
        } else {
            if (in_array('truncate', $check)) {
                $this->addLine("TRUNCATE TABLE {$table_name};");
                $this->emptyLine();
            }
        }


        if (!in_array('create', $check)) {
            return $this;
        }


        $this->addLine('--');
        $this->addLine('-- 表的结构 ' . $table_name);
        $this->addLine('--');

        $this->emptyLine();

        $table = DB::getPdo()->query('SHOW CREATE TABLE ' . $table_name);
        //->fetch_assoc();
        //$table = $table[0];
        foreach ($table as $k => $t) {
            $create_query = $t[1];
        }
        //$create_query = $table[1];
        if (mb_strpos($create_query, "(\r\n ")) {
            $create_query = str_replace("\r\n", $crlf, $create_query);
        } elseif (mb_strpos($create_query, "(\n ")) {
            $create_query = str_replace("\n", $crlf, $create_query);
        } elseif (mb_strpos($create_query, "(\r ")) {
            $create_query = str_replace("\r", $crlf, $create_query);
        }
        $this->addLine($create_query . ';');

        return $this;
    }

    private function delimite(string $s): string
    {
        return '`' . str_replace('`', '``', $s) . '`';
    }

    public function dumpToFile($file)
    {
        $fopen = fopen($file, 'w+');
        fwrite($fopen, $this->content);
        fclose($fopen);
        return;
    }

    public function exportTable($table_name, $where = '', $check = [])
    {
        $config = config('database.connections.' . env('DB_CONNECTION'));
        $prefix = $config['prefix'] ?? '';
        $table_name = $this->delimite($prefix . $table_name);

        $crlf = $this->crlf;

        $this->addLine('-- DeAdmin SQL Dump');
        $this->emptyLine();

        $this->exportStructure($table_name, $check);

        $this->emptyLine();
        $this->addLine('--');
        $this->addLine('-- 转存表中的数据 ' . $table_name);
        $this->addLine('--');
        $this->emptyLine();

        if (in_array('insert', $check)) {
            $command = 'INSERT';
        } else {
            //默认使用replace模式
            $command = 'REPLACE';
        }

        $numeric = [];
        $res = DB::getPdo()->query('SHOW COLUMNS FROM ' . $table_name);
        $cols = [];
        foreach ($res as $row) {
            //d($val);
            $col = $row['Field'];
            $cols[] = $this->delimite($col);
            $numeric[$col] = (bool) preg_match('#^[^(]*(BYTE|COUNTER|SERIAL|INT|LONG$|CURRENCY|REAL|MONEY|FLOAT|DOUBLE|DECIMAL|NUMERIC|NUMBER)#i', $row['Type']);
        }
        $cols = '(' . implode(', ', $cols) . ')';

        $size = 0;
        $count_data = DB::getPdo()->query("SELECT count(*) FROM " . $table_name . ($where ? " WHERE " . $where : ""));
        $count = 0;
        foreach ($count_data as $c) {
            $count = $c[0];
        }
        $res = DB::getPdo()->query("SELECT * FROM " . $table_name . ($where ? " WHERE " . $where : ""));
        foreach ($res as $row) {
            $s = '(';
            $values = [];
            foreach ($row as $key => $value) {
                if (is_numeric($key)) {
                    continue;
                }
                if ($value === null) {
                    $values[] = "NULL";
                } elseif ($numeric[$key]) {
                    $values[] = $value;
                } else {
                    $values[] = DB::getPdo()->quote($value);
                }
            }

            $s .= implode(",\t", $values);
            $s .= '),';
            if ($size == 0) {
                $s = "{$command} INTO $table_name $cols VALUES{$crlf}$s";
            }



            $size += 1;
            if ($size >= $this->max_sql_size || $size == $count) {
                $s = substr($s, 0, -1);
                $s .= ";{$crlf}";
                $size = 0;
            }

            $this->addLine($s);
        }

        return $this;
    }

    public function import($content,$to_menu_id = 0,$to_model_id = 0)
    {
        if (!$content) {
            return [1, '请上传系统导出的sql文件'];
        }

        $content = HelperService::json_validate($content);
        if (!$content) {
            return [1, '文件格式错误'];
        }

        $model_count = $relation_count = $menu_count = 0;

        $menu_ids = [];

        foreach ($content as $val) {
            $menu = $val['menu'] ?? false;
            if ($menu) {
                $menu_count++;
                $menu['parent_id'] = $to_menu_id ? : $menu['parent_id'];
                Menu::updateOrInsert(['id' => $menu['id']], $menu);
                $menu_ids[] = $menu['id'];
            }
            $model = $val['model'] ?? false;
            if ($model) {
                $model['parent_id'] = $to_model_id ? : $model['parent_id'];
                Model::updateOrInsert(['id' => $model['id']], $model);
                $model_count++;
                //清除模型字段缓存
                (new Schema)->clearTableColumnsCache($model);
            }
            $relations = $val['relations'] ?? false;
            if ($relations) {
                foreach ($relations as $v) {
                    Relation::updateOrInsert(['id' => $v['id']], $v);
                    $relation_count++;
                }
            }
        }
        return [0, '操作成功，模型:' . $model_count . ' 菜单:' . $menu_count . ' 关系:' . $relation_count,$menu_ids];
    }

    public function exportModel($model_id)
    {
        //新增递归将所有上级数据也都导出（当导出一个新模型的时候如果有上级的话程序就炸了）暂时不去重了
        $model = Model::where(['id'=>$model_id])->with(['relations'])->first();
        $datas = [];
        if(!$model)
        {
            return $datas;
        }
        $model = $model->toArray();
        if($model['parent_id'])
        {
            //存在上级 导出上级数据
            $datas = array_merge($datas,$this->exportModel($model['parent_id']));
        }
        $relations = $model['relations'];
        unset($model['relations']);
        $datas[] = [
            'model' => $model,
            'relations' => $relations
        ];
        return $datas;
    }

    public function export($ids = [], $type = 'model')
    {
        $filename = 'export.' . date("Ymdhis") . '.sql';

        $file = storage_path('app/public/' . $filename);

        if (empty($ids)) {
            return [1, '请先勾选需要导出的模型数据'];
        }
        $json = [];
        if($type == 'model')
        {
            foreach ($ids as $model_id) 
            {
                $json = array_merge($json,$this->exportModel($model_id));
            }
        }else
        {
            //导出菜单
            $menus = Menu::whereIn('id', $ids)->get()->toArray();
            foreach ($menus as $menu) 
            {
                $json[] = ['menu'=>$menu];
                if($menu['admin_model_id'])
                {
                    $json = array_merge($json,$this->exportModel($menu['admin_model_id']));
                }
            }
        }

        $this->content = json_encode($json);
        $this->dumpToFile($file);
        return [0,['url' => tomedia($filename), 'download' => $filename]];
    }
}
