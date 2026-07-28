<?php

namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\admin\LocaleService;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\HelperService;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FakeData
{
    public $model_class = null;

    /**
     * Undocumented variable
     *
     * @var Generator
     */
    public $faker = null;

    /**
     * Undocumented variable
     *
     * @var Generator
     */
    public $faker_back = null;

    public $select_id_map = []; // 读取class中的关联获取关联模型中的id数组

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
        $this->model_class = $class; // 保存当前模型的class
        $this->getRelationIds();

        $table_columns = HelperService::json_validate($model->columns);

        if (! $table_columns) {
            return '模型字段不存在';
        }

        $datas = [];
        $this->faker = $this->getFaker();
        $this->faker_back = $this->getFaker('zh_TW');
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

    public function getRelationIds()
    {
        if (! $this->model_class) {
            return;
        }
        $model = new $this->model_class;
        $columns = $model->getParseColumns();
        foreach ($columns as $column) {
            $type = Arr::get($column, 'type');
            $class = Arr::get($column, 'class');
            $name = Arr::get($column, 'name');
            if (in_array($type, ['select', 'selects'])) {
                if ($class) {
                    $class_model = new $class;
                    $this->select_id_map[$name] = $class_model->limit(100)->pluck('id')->toArray();
                } else {
                    $data = Arr::get($column, 'data');
                    if ($data) {
                        $this->select_id_map[$name] = collect($data)->map(fn ($v) => $v['id'])->toArray();
                    }

                }

            }

        }

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

    public function getFaker($lang = '')
    {
        if (! class_exists('Faker\Factory')) {
            return false;
        }
        $faker = Factory::create($lang ?: $this->getLang());

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
        $faker = $this->faker;
        $hidden_columns = [];
        $lang = $this->getLang();
        foreach ($columns as $column) {
            $random_items = [];
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
            if (! $type) {
                // 下面通过字段名称默认配置生成数据的类型
                $fieldnames = [
                    'username' => 'username',
                    'address' => 'address',
                    'company' => 'company',
                    'mobile' => 'phoneNumber',
                    'phone' => 'phoneNumber',
                    'tel' => 'phoneNumber',
                    'email' => 'email',
                ];
                $type = $fieldnames[$name] ?? false;
                if (! $type) {
                    $type_map = [
                        'switch' => 'randomNumber',
                        'image' => 'image',
                        'radioButton' => 'randomStr',
                        'tinyEditor' => 'content',
                        'textarea' => 'text',
                        'varchar' => 'text',
                        'select' => 'randomStr',
                        'selects' => 'randomStr',
                        'pca' => 'pca',
                        'mapInput' => 'mapInput',
                        'datetime' => 'datetime',
                        'date' => 'date',
                        'digit' => 'digit',
                        'email' => 'email',
                    ];
                    // 如果没有设定值类型，则根据form_type自动生成值
                    $form_type = Arr::get($column, 'form_type', Arr::get($column, 'type'));
                    $type = Arr::get($type_map, $form_type);
                    if (in_array($form_type, ['select', 'selects', 'modalSelect']) && isset($this->select_id_map[$name])) {
                        $random_items = $this->select_id_map[$name];
                    }
                }

            }

            $fake_options = Arr::get($column, 'fake_options');

            switch ($type) {
                case 'text':
                    $v = $lang == 'zh_CN' ? $this->faker_back->realText(20) : $faker->text(20);
                    break;
                case 'content':
                    $v = $lang == 'zh_CN' ? $this->faker_back->realText(200) : $faker->paragraphs(5, true);
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
                    [$min,$max] = $fake_options ? explode(',', $fake_options) : [0, 1];
                    $v = $faker->numberBetween(intval($min), intval($max));
                    break;
                case 'digit':
                    $v = $faker->numberBetween(10, 1000);
                    break;
                case 'randomStr':
                    $fake_options = $fake_options ? (! is_numeric($fake_options) ? explode(',', $fake_options) : $fake_options) : $random_items;
                    if (is_array($fake_options) && ! empty($fake_options)) {
                        $v = $faker->randomElement(array_unique($fake_options));
                    } else {
                        $v = Str::random(is_numeric($fake_options) ? $fake_options : 10);
                    }
                    break;
                case 'password':
                    $v = $faker->password();
                    break;
                case 'image':
                    $v = json_encode([['value' => 'example.png']]);
                    break;
                case 'pca':
                    // 随机一个省市区
                    $v = '310000'; // 上海市
                    $data['city'] = '310100'; // 上海市
                    $data['area'] = $faker->randomElement(['310101', '310104', '310105', '310106', '310107', '310109', '310110', '310112', '310113', '310114', '310115', '310116', '310117', '310118', '310120', '310151']);
                    break;
                case 'mapInput':// 地图选点
                    $v = $faker->randomFloat(8, 31.20869, 31.25097);
                    $data['lng'] = $faker->randomFloat(8, 121.4027, 121.49231);
                    break;
                case 'datetime':
                    $v = $faker->dateTimeBetween('-1 years', 'now')->format('Y-m-d H:i:s');
                    break;
                case 'date':
                    $v = $faker->dateTimeBetween('-1 years', 'now')->format('Y-m-d');
                    break;
                case 'email':
                    $v = $faker->email();
                    break;
            }

            if ($v !== false) {
                if (! isset($data[$name])) {
                    // 未设置的字段，直接赋值
                    $data[$name] = $v;
                }

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
