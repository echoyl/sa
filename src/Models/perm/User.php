<?php

namespace Echoyl\Sa\Models\perm;

use Echoyl\Sa\Models\BaseAuth;
use Echoyl\Sa\Models\personal\access\Tokens;

class User extends BaseAuth
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'perm_user';

    public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $data = [
                [
                    'name' => 'role',
                    'type' => 'model',
                    'class' => Role::class,
                    'foreign_key' => 'id',
                ],
                [
                    'name' => 'log',
                    'type' => 'model',
                    'class' => Tokens::class,
                    'foreign_key' => 'tokenable_id',
                ],
                [
                    'name' => 'roleid',
                    'type' => 'select',
                    'default' => 0,
                    'data' => (new Role)->get()->toArray(),
                    'with' => true,
                ],
                [
                    'name' => 'password',
                    'type' => 'password',
                    'default' => '',
                ],
                [
                    'name' => 'avatar',
                    'type' => 'image',
                    'default' => '',
                ],
                [
                    'name' => 'state',
                    'type' => 'switch',
                    'default' => 0,
                    'table_menu' => true,
                    'with' => true,
                    'data' => [
                        [
                            'label' => '禁用',
                            'value' => 0,
                        ],
                        [
                            'label' => '启用',
                            'value' => 1,
                        ],
                    ],
                ],
            ];
        }

        return $data;
    }

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'roleid');
    }

    public function log()
    {
        return $this->hasOne(Tokens::class, 'tokenable_id', 'id')->where([['name', '=', 'admin']])->orderBy('last_used_at', 'desc');
    }
}
