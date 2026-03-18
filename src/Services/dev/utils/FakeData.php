<?php

namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\admin\LocaleService;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\HelperService;
use Faker\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FakeData
{
    /**
     * Generate fake data
     *
     * @param ["columns" => [], "count" => 200, "id" => 0] $params 字段类型
     * @return void
     */
    public function generate($params = [])
    {
        $faker = $this->getFaker();
        if (! $faker) {
            return '请先安装 composer require fakerphp/faker --dev';
        }

        $columns = $params['columns'] ?? []; // 提交的数据，可能为空应该默认值时 前端不会传数据过来
        $count = $params['count'] ?? 200;

        if ($count <= 0) {
            return '数量必须大于0';
        }

        $model_id = $params['id'] ?? 0;
        $model_detail = $this->getModel($model_id);
        if (! $model_detail) {
            return '模型不存在';
        }
        [$class,$model] = $model_detail;

        $table_columns = HelperService::json_validate($model->columns);

        if (! $table_columns) {
            return '模型字段不存在';
        }

        $datas = [];
        $form_columns = $this->getColumnsValue($table_columns, $columns);
        for ($i = 0; $i < $count; $i++) {
            $item = $this->getItem($form_columns);
            // d($item);
            $datas[] = $item;
        }

        // d($form_columns);

        $class::upsert($datas, ['id']);

        // 保存设置 之后如果需要生成可以直接读取配置信息
        $this->updateModelColumns($model, $form_columns);

        return true;

    }

    public function getColumnsValue($table_columns, $columns)
    {
        $value = [];
        foreach ($columns as $column) {
            $value[$column['name']] = $column;
        }
        foreach ($table_columns as $key => $column) {
            $form_value = Arr::get($value, $column['name']);
            if ($form_value) {
                $fake_type = Arr::get($form_value, 'fake_type');
                $fake_options = Arr::get($form_value, 'fake_options');
                if ($fake_type) {
                    $table_columns[$key]['fake_type'] = $fake_type;
                }
                if ($fake_options) {
                    $table_columns[$key]['fake_options'] = $fake_options;
                }
            }
        }

        return $table_columns;
    }

    public function getLang()
    {
        $lang = LocaleService::getLang();
        // 存在前端语言名称需要转化的
        $cust = [
            'zh-CN' => 'zh_CN',
            'en-US' => 'en_US',
        ];

        return $cust[$lang] ?? $lang;
    }

    public function getFaker()
    {
        if (! class_exists('Faker\Factory')) {
            return false;
        }
        $faker = Factory::create($this->getLang());

        return $faker;
    }

    public function getItem($columns)
    {
        //           { label: 'text - 文本', value: 'text' },
        //           { label: 'content - 内容', value: 'content' },
        //           { label: 'address - 地址', value: 'address' },
        //           { label: 'name - 姓名', value: 'username' },
        //           { label: 'company - 公司', value: 'company' },
        //           { label: 'phoneNumber - 电话', value: 'phoneNumber' },
        //           { label: 'randomNumber - 随机数', value: 'randomNumber' },
        //           { label: 'randomStr - 随机字符串', value: 'randomStr' },
        //           { label: 'password - 密码', value: 'password' },

        $data = [];
        $skip_columns = ['id', 'created_at', 'updated_at', 'displayorder'];
        $faker = $this->getFaker();
        $hidden_columns = [];
        foreach ($columns as $column) {

            $name = Arr::get($column, 'name');
            if (substr($name, 0, 1) == '_') {
                // 以 _ 开始的字段都是隐藏字段 为了包含选中数据全部信息的数据，都是json格式
                $no_ = substr($name, 1);
                if (isset($data[$no_])) {
                    $data[$no_] = json_encode([$data[$no_]]);
                } else {
                    $hidden_columns[] = $no_;
                }

                continue;
            }
            if (in_array($name, $skip_columns)) {
                continue;
            }
            $type = Arr::get($column, 'fake_type');
            $v = false;
            if ($type) {
                // 如果设定了值的类型则使用设定的值

                $fake_options = Arr::get($column, 'fake_options');

                switch ($type) {
                    case 'text':
                        $v = $faker->text(50);
                        break;
                    case 'content':
                        $v = $faker->paragraphs(5, true);
                        break;
                    case 'address':
                        $v = $faker->address();
                        break;
                    case 'username':
                        $v = $faker->name();
                        break;
                    case 'company':
                        $v = $faker->company();
                        break;
                    case 'phoneNumber':
                        $v = $faker->phoneNumber();
                        break;
                    case 'randomNumber':
                        if ($fake_options) {
                            [$min,$max] = explode(',', $fake_options);
                            $v = $faker->numberBetween(intval($min), intval($max));
                        } else {
                            $v = $faker->randomNumber(6);
                        }
                        break;
                    case 'randomStr':
                        if ($fake_options) {
                            $v = $faker->randomElement(explode(',', $fake_options));
                        } else {
                            $v = Str::random(10);
                        }
                        break;
                    case 'password':
                        $v = $faker->password();
                        break;

                }
            } else {
                // 根据form_type 自动生成值
                $form_type = Arr::get($column, 'form_type');
                switch ($form_type) {
                    case 'switch':
                        $v = $faker->numberBetween(0, 1);
                        break;
                    case 'image':
                        $v = json_encode([['value' => 'example.png']]);
                        break;
                    case 'radioButton':
                        $json = Arr::get($column, 'setting.json');
                        if ($json) {
                            $v = $faker->randomElement(collect($json)->map(fn ($v) => $v['id'])->toArray());
                        }
                }

            }
            if ($v !== false) {
                $data[$name] = $v;
                if (in_array($name, $hidden_columns)) {
                    $data['_'.$name] = json_encode([$v]);
                }
            }
        }

        return $data;
    }

    public function getModel($id)
    {
        $model = Model::where(['id' => $id])->first();
        if (! $model) {
            return false;
        }
        $ds = new DevService;
        [,,$classname] = $ds->getNamespace($model); // 这个是选中的模型

        if (class_exists($classname)) {
            return [get_class(new $classname), $model];
        }

        return false;
    }

    public function updateModelColumns($model, $columns)
    {
        $update = [
            'columns' => json_encode($columns),
        ];
        Model::where(['id' => $model['id']])->update($update);

    }
}
