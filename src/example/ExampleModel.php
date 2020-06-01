<?php
/**
 * 模型
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/1
 * Time: 14:35
 */

namespace Wenruns\Excel\example;

use Illuminate\Database\Eloquent\Model;

class ExampleModel extends Model
{
    protected $table = 'table';

    protected $primaryKey = 'id';
}