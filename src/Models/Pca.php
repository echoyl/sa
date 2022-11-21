<?php
/*
 * @Author: echoyl yliang_1987@126.com
 * @Date: 2022-09-26 11:31:43
 * @LastEditors: echoyl yliang_1987@126.com
 * @LastEditTime: 2022-11-14 10:39:24
 * @FilePath: \donglifengdianchi\vendor\echoyl\sa\src\Models\Pca.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */

namespace Echoyl\Sa\Models;

use Illuminate\Database\Eloquent\Model;

class Pca extends Model
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'pca';
    public $timestamps = false;
    public function children()
    {
        return $this->hasMany(self::class, 'pcode', 'code');
    }

}
