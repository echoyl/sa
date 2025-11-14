<?php

namespace Echoyl\Sa\Traits;

trait CheckUnique
{
    public function checkUnique($data, $id = 0, $class = null)
    {
        $model = $class ? new $class : $this->getModel();
        if (property_exists($this, 'uniqueFields') && ! empty($this->uniqueFields)) {
            $uniqueFields = $this->uniqueFields;
        } else {
            $uniqueFields = property_exists($model, 'uniqueFields') ? $model->uniqueFields : [];
        }
        if (empty($uniqueFields)) {
            return;
        }
        $key = '';
        // $message = '';
        $is_has = false;
        foreach ($uniqueFields as $field) {

            $where = [];
            if (is_array($field)) {
                // 增加了提示语 检测格式 如果有 columns和message
                $keys = [];
                foreach ($field as $k => $v) {
                    if (is_numeric($k)) {
                        $keys[] = $v;
                    } else {
                        if ($k == 'columns') {
                            $keys = $v;
                        }
                        if ($k == 'message') {
                            $key = $v;
                        }
                    }
                }
                $key = $key ? $key : implode('-', $keys).'数据已存在';
                foreach ($keys as $f) {
                    if (! isset($data[$f]) || ! $data[$f]) {
                        // 未设置该值或无该值时不进行检测
                        continue;
                    }
                    $where[$f] = $data[$f];
                }
            } else {
                if (! isset($data[$field]) || ! $data[$field]) {
                    continue;
                }
                $where[$field] = $data[$field];
                $key = $field;
            }
            if (empty($where)) {
                continue;
            }

            $has = $model->where($where);
            if ($id) {
                $has = $has->where([['id', '!=', $id]]);
            }
            if ($has->first()) {
                $is_has = true;
                break;
            }
        }

        return $is_has ? $key : '';
    }
}
