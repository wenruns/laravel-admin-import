<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/7
 * Time: 15:05
 */

namespace App\Admin\Services\Excel\commission;


use App\Admin\Services\Excel\ExcelService;
use App\Admin\Services\Excel\Response;
use App\Admin\Services\Excel\ShowLayer;
use App\Models\Bank;
use App\Models\ImportSalesCommission;
use App\Models\ImportSalesCommissionLog;
use App\Models\ImportSalesDataLog;
use App\Models\LoanForm;
use Encore\Admin\Facades\Admin;

class CommissionService extends ExcelService
{
    public function format($data)
    {
//        try {
        // TODO: Implement format() method.
        $formatData = $this->parseData($data);
        if ($formatData === false) {
            return false;
        }
        $key_name = 'data';
        $formatData = $this->initData($formatData);

        $info = LoanForm::where('bank_loan_id', $formatData['period_number'])->first();
        if (empty($info)) {
            $formatData['err_msg'] = '该客户没有申请信息';
            $formatData['org_code'] = Admin::user()->org_code;
            $formatData['department_code'] = Admin::user()->department_code;
            $key_name = 'errData';
        } else {
            $formatData['custom_name'] = $info->name;
            $formatData['org_code'] = $info->org_code;
            $formatData['department_code'] = $info->department_code;
            $formatData['apply_id'] = $info->apply_id;
            // 获取银行标志号
            $bank_shortcut = Bank::where('bank_id', $info->bank_id)->value('shortcut');
            if (empty($bank_shortcut)) {
                $formatData['err_msg'] = '没找到银行短标记';
                $key_name = 'errData';
            } else {
                $formatData['bank_shortcut'] = $bank_shortcut;
            }
        }
        if (ImportSalesCommission::where('unique', $formatData['unique'])->first()) {
            $formatData['err_msg'] = '数据库已存在';
            $key_name = 'existData';
        }
        return [
            $key_name => $formatData,
        ];
//        } catch (\Exception $e) {
//            dd($e);
//        }
    }

    protected function parseData($data)
    {
        $empty = true;
        $header = $this->setHeader();
        foreach ($header as $field => $text) {
            if (!array_key_exists($text, $data)) {
                throw new \Exception('以下名称发生错误：《' . $text . '》。请严格按照模板文件填写！<a href="' . route('admin.bank_sale.downloadTpl') . '" target="_blank" style="font-size: .8em;line-height: 30px;">点我下载模板文件</a>');
            }
            if ($empty && ($data[$text] || !empty(trim($data[$text])))) {
                $empty = false;
            }
            if ($field == 'trade_date') {
                $data[$text] = strtotime(date('Y-m', strtotime($data[$text])));
            }
            $formatData[$field] = $data[$text];
        }
        if (!isset($formatData) || $empty) {
            return false;
        }
        return $formatData;
    }

    protected function initData($formatData)
    {
        $unique = md5(json_encode($formatData));
        $formatData['origin_commission'] = '' . $formatData['commission'];
        $formatData['commission'] = '' . round($formatData['commission'], 2);
        $formatData['unique'] = $unique;
        $formatData['err_msg'] = '正常数据';
        $formatData['created_at'] = time();
        $formatData['updated_at'] = time();
        $formatData['org_code'] = NULL;
        $formatData['department_code'] = NULL;
        $formatData['apply_id'] = NULL;
        $formatData['bank_shortcut'] = NULL;
        return $formatData;
    }

    public function setHeader(): array
    {
        // TODO: Implement setHeader() method.
        return [
            'custom_name' => '姓名',
            'period_number' => '银行贷款编码',
            'trade_date' => '支用月份',
            'commission' => '返佣金额',
        ];
    }


    /**
     * @param Response $response
     */
    public function successCallback(Response $response)
    {
        $result = $response->getResult();
        $files = $this->getExcelFiles();
        $fileNames = array_column($files, 'fileName');
        if ($result['code'] == 200 && !empty($result['res'])) {
//            $logData = [
//                'staff_code' => Admin::user()->staff_code,
//                'staff_name' => Admin::user()->staff_name,
//                'file_name' => implode(',', $fileNames),
//                'import_sales_commission_ids' => json_encode($result['res']),
//                'created_at' => time(),
//                'updated_at' => time(),
//            ];
//            ImportSalesCommissionLog::insert($logData);
            $logData = [
                'staff_code' => Admin::user()->staff_code,
                'staff_name' => Admin::user()->staff_name,
                'file_name' => implode(',', $fileNames),
                'import_ids' => json_encode($result['res']),
                'type' => ImportSalesDataLog::IMPORT_TYPE_COMMISSION,
                'created_at' => time(),
                'updated_at' => time(),
            ];
            ImportSalesDataLog::insert($logData);
        }

        $success = count($response->getSuccessData());
        $fail = count($response->getErrorData());
        $repeat = count($response->getExistData());
        $content = <<<THML
<table style="border: 1px solid pink;width: 100%;">
    <tr style="border: 1px solid pink">
       <td style="padding: 5px; text-align: center;width: 50%;">成功</td>
       <td style="padding: 5px; text-align: center;width: 50%;">{$success}条</td>
    </tr>
    <tr style="border: 1px solid pink">
       <td style="padding: 5px; text-align: center;width: 50%;">失败</td>
       <td style="padding: 5px; text-align: center;width: 50%;">{$fail}条</td>
    </tr>
    <tr>
       <td style="padding: 5px; text-align: center;width: 50%;">重复</td>
       <td style="padding: 5px; text-align: center;width: 50%;">{$repeat}条</td>
    </tr>
</table>
THML;
        (new ShowLayer([
            'title' => '导入结果',
            'type' => 'success',
            'content' => $content,
            'showCancelButton' => false,
            'confirmButtonText' => '知道了',
        ]))->render();
    }

    public function failCallback(Response $response)
    {
        $result = $response->getResult();
        $reason = $result['errMsg'];
        $content = <<<HTML
<table style="border: 1px solid pink;width: 100%;">
    <tr style="border: 1px solid pink">
        <td style="padding: 5px; text-align: center;width: 60px; border-right: 1px solid pink;">原因</td>
        <td style="padding: 5px; text-align: left;">{$reason}</td>
    </tr>
</table>
HTML;
        (new ShowLayer([
            'title' => '导入失败',
            'content' => $content,
            'showCancelButton' => false,
            'confirmButtonText' => '知道了',
            'type' => 'error',
        ]))->render();
    }
}