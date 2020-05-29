<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/7
 * Time: 15:05
 */

namespace App\Admin\Services\Excel\commission;


use App\Admin\Services\Excel\Response;
use App\Admin\Services\Excel\ShowLayer;
use App\Models\Bank;
use App\Models\ImportSalesCommission;
use App\Models\ImportSalesCommissionLog;
use App\Models\ImportSalesDataLog;
use App\Models\LoanForm;
use Encore\Admin\Facades\Admin;
use Wenruns\Excell\ExcelService;

class ExampleService extends ExcelService
{
    /**
     * @param $data
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
     * @param Response $response
     */
    public function successCallback(Response $response)
    {

    }

    public function failCallback(Response $response)
    {
    }
}