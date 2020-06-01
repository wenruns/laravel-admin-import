<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/7
 * Time: 15:05
 */

namespace Wenruns\Example;


use Wenruns\Import\ExcelService;
use Wenruns\Import\Response;

class ExampleService extends ExcelService
{

    public function header()
    {
        // TODO: Implement header() method.
        return [
            'field1' => '标题1',
            'field2' => '标题2',
            'field3' => '标题3',
            'field4' => '标题4',
        ];
    }

    /**
     * 格式化导入的数据
     * @param $data 导入数据
     * @return array|bool
     * @throws \Exception
     */
    public function format($data)
    {
        // todo:: 格式化导入数据
        return [
            'data' => [], // 正常数据
            'errData' => [], // 异常数据（错误的数据）
            'existData' => [], // 重复的数据
        ];
    }


    /**
     * 导入成功回调
     * @param Response $response
     */
    public function successCallback(Response $response)
    {

    }

    /**
     * 导入失败回调
     * @param Response $response
     */
    public function failCallback(Response $response)
    {
    }
}