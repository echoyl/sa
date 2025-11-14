<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Models\Pca;

// customer namespace start

// customer namespace end

class PcaController extends CrudController
{
    // customer property start

    // customer property end
    public function __construct()
    {
        parent::__construct();
        $this->uniqueFields = [
            [
                'columns' => [
                    'code',
                ],
                'message' => '行政编码已存在',
            ],
        ];
        $this->model = new Pca;
        $this->model_class = Pca::class;
        // customer construct start
        $this->with_column = ['parent'];
        $this->displayorder = [['id', 'asc']];
        // customer construct end
    }

    // customer code start
    public function handleSearch($search = [])
    {
        $m = $this->getModel();

        $pcode = request('pcode', 0);
        $pcode = is_string($pcode) ? json_decode($pcode, true) : [$pcode];
        $last_code = array_pop($pcode);
        $m = $m->where('pcode', $last_code);

        return [$m, $search];
    }

    public function beforePost(&$data, $id = 0, $item = [])
    {
        if (isset($data['pcode'])) {
            $pcode = $data['pcode'];
            $data['pcode'] = array_pop($pcode);
        }
    }

    public function postData(&$item)
    {
        if (isset($item['pcode'])) {
            $p = $item['parent'];
            if ($p) {
                $item['pcode'] = $p['pcode'] ? [$p['pcode'], $p['code']] : [$p['code']];
            } else {
                unset($item['pcode']);
            }
        }
    }
    // customer code end

}
