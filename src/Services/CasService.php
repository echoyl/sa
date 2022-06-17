<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\Pca;

class CasService
{

    public function cascader($level = 3)
    {
        $model = new Pca();
        switch ($level) {
            case 1:
                $provinces = $model->select(['name as label', 'code as value'])->where(['pcode' => 0])->get()->toArray();
                break;
            case 2:
                $provinces = $model->select(['name as label', 'code as value', 'code'])->where(['pcode' => 0])->with(['children' => function ($q) {
                    $q->select(['name as label', 'code as value', 'pcode']);
                }])->get()->toArray();
                break;
            default:
                $provinces = $model->select(['name as label', 'code as value', 'code'])->where(['pcode' => 0])->with(['children' => function ($q) {
                    $q->select(['name as label', 'code as value', 'pcode', 'code'])->with(['children' => function ($query) {
                        $query->select(['name as label', 'code as value', 'pcode']);
                    }]);
                }])->get()->toArray();
        }
        return $provinces;
    }
}
