<?php
namespace Echoyl\Sa\Services;

use Echoyl\Sa\Models\Pca;

class CasService
{

    public function cascader($level = 3,$topCode = '')
    {
        $model = new Pca();
        $topCode = $topCode?explode(',',$topCode):[];
        $top_level = count($topCode);
        if($top_level > 0)
        {
            $pcode = array_pop($topCode);
        }else
        {
            $pcode = 0;
        }

        switch ($level) {
            case 1:
                $provinces = $model->select(['name as label', 'code as value'])->where(['pcode' => $pcode])->get()->toArray();
                break;
            case 2:
                $provinces = $model->select(['name as label', 'code as value', 'code'])->where(['pcode' => $pcode])->with(['children' => function ($q) {
                    $q->select(['name as label', 'code as value', 'pcode']);
                }])->get()->toArray();
                break;
            default:
                $provinces = $model->select(['name as label', 'code as value', 'code'])->where(['pcode' => $pcode])->with(['children' => function ($q) {
                    $q->select(['name as label', 'code as value', 'pcode', 'code'])->with(['children' => function ($query) {
                        $query->select(['name as label', 'code as value', 'pcode']);
                    }]);
                }])->get()->toArray();
        }
        
        return $provinces;
    }
}
