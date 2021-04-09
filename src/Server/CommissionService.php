<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/7
 * Time: 15:05
 */

namespace App\Admin\Services\Excel\Server;


use App\Admin\Services\Excel\ExcelService;
use App\Admin\Services\Excel\Response;
use App\Models\FaceSign;
use App\Models\ImportSalesCommission;
use App\Models\ImportSalesCommissionLog;
use App\Models\ImportSalesData;
use App\Models\ImportSalesDataLog;
use App\Models\ImportSalesReconciliation;
use App\Models\MakeLoan;
use App\Models\Tuangou\LoanView;
use App\Models\UploadFile;
use App\Services\Common\SourceFileService;
use Encore\Admin\Facades\Admin;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CommissionService extends ExcelService
{

    public function format($data): ExcelService
    {
        // TODO: Implement format() method.
        $formatData = $this->parseData($data);
        if ($formatData === false) {
            return $this;
        }
        $key_name = 'data';
        $formatData = $this->formatData($formatData);

        // 从新系统中检测数据
        $info = FaceSign::where('face_sign.bank_loan_id', $formatData['period_number'])
            ->leftJoin('loan_form', 'loan_form.apply_id', '=', 'face_sign.apply_id')
            ->leftJoin('bank', 'bank.bank_id', '=', 'loan_form.bank_id')
            ->select(['face_sign.*', 'loan_form.loan_period', 'bank.shortcut'])
            ->first();
        if ($info) {
            $formatData['system'] = ImportSalesData::SYSTEM_BKQW_LOAN;
            $formatData['custom_name'] = $info->name;
            $formatData['org_code'] = $info->org_code;
            $formatData['department_code'] = $info->department_code;
            $formatData['apply_id'] = $info->apply_id;
            $formatData['loan_period'] = $info->loan_period;
            $formatData['product_id'] = $info->product_id;
            $formatData['bank_shortcut'] = $info->shortcut;
        } else {
            // 从旧系统中检测数据
            $info = LoanView::where('ccb_apply_no', $formatData['period_number'])->first();
            if ($info) {
                $org_code = [
                    '2' => 'D001',
                    '3' => 'D015',
                    '10' => 'D002',
                    '11' => 'D003',
                    '4' => 'D013',
                    '12' => 'D017',
                    '13' => 'D019',
                ];
                $formatData['system'] = ImportSalesData::SYSTEM_TTTUANGOU;
                $formatData['apply_id'] = $info->id;
                $formatData['custom_name'] = $info->host_name;
                $formatData['org_code'] = $org_code[$info->cityid] ?? $info->cityid;
                $formatData['department_code'] = '';
                $formatData['bank_shortcut'] = $info->shortcut;
                $formatData['loan_period'] = $info->loan_periods;
            } else {
                $formatData['err_msg'] = '该客户没有申请信息';
                $formatData['org_code'] = Admin::user()->org_code;
                $formatData['department_code'] = Admin::user()->department_code;
                $key_name = 'errData';
            }
        }

        if (isset($formatData['apply_id'])) {
            $loan_money = MakeLoan::where('apply_id', $formatData['apply_id'])->sum('make_loan_money');
            if (empty($loan_money) || $loan_money <= 0) {
                $formatData['err_msg'] = '该笔贷款尚未放款';
                $key_name = 'errData';
            }
        }


        if (ImportSalesCommission::where('unique', $formatData['unique'])->first()) {
            $formatData['err_msg'] = '数据库已存在';
            $key_name = 'existData';
        }
        switch ($key_name) {
            case 'data':
                $this->setNormalData($formatData);
                break;
            case 'errData':
                $this->setErrorData($formatData);
                break;
            case 'existData':
                $this->setAbnormalData($formatData);
                break;
            default:
        }

        return $this;
    }


    protected function parseData($data)
    {
        $empty = true;
        $errMsg = '';
        $header = $this->header();
        foreach ($header as $field => $text) {
            if (!array_key_exists($text, $data)) {
                throw new \Exception('以下名称发生错误：《' . $text . '》。请严格按照模板文件填写！<a href="' . route('admin.bank_sale.downloadTpl') . '" target="_blank" style="font-size: .8em;line-height: 30px;">点我下载模板文件</a>');
            }
            $item = trim($data[$text]);
            $item = mb_ereg_replace('(　| | )+', '', $item);

            if ($empty && !empty($item)) {
                $empty = false;
            }
            if ($field == 'trade_date') {
                $timeStamp = strtotime($item);
                if ($timeStamp == false || $timeStamp < 0) {
                    $errMsg = '导入支用月份格式错误，请确认支用月份格式为：xxxx年xx月';
                }
                $item = strtotime(date('Y-m', $timeStamp));
            }
            $formatData[$field] = $item;
        }
        if (!isset($formatData) || $empty) {
            return false;
        }
        if ($errMsg) {
            throw new \Exception($errMsg);
        }
        return $formatData;
    }


    protected function formatData($formatData)
    {
        $unique = md5(trim($formatData['period_number']) . ($formatData['commission'] + 0) . $formatData['trade_date']);
        $formatData['unique'] = $unique;
        $formatData['err_msg'] = '正常数据';
        $formatData['created_at'] = time();
        $formatData['updated_at'] = time();
        $formatData['loan_period'] = NULL;
        $formatData['org_code'] = NULL;
        $formatData['department_code'] = NULL;
        $formatData['apply_id'] = NULL;
        $formatData['bank_shortcut'] = NULL;
        $formatData['product_id'] = NULL;
        $formatData['system'] = ImportSalesData::SYSTEM_UNLINK;

        return $formatData;
    }

    /**
     * 字段映射
     * @return array
     */
    public static function header()
    {
        // TODO: Implement header() method.
        return [
            'custom_name' => '姓名',
            'period_number' => '银行贷款编码',
            'trade_date' => '支用月份',
            'commission' => '返佣金额',
        ];
    }


    /**
     * 成功回调
     * @param Response $response
     */
    public function successCallback(Response $response)
    {
        $result = $response->getResult();
        $files = $this->getExcelFiles();
        $fileNames = array_column($files, 'fileName');
        $file_ids = array_column($files, 'data');
        if ($result['code'] == 200 && !empty($result['res'])) {
            $logData = [
                'staff_code' => Admin::user()->staff_code,
                'staff_name' => Admin::user()->staff_name,
                'file_name' => implode(',', $fileNames),
                'import_ids' => json_encode($result['res']),
                'type' => ImportSalesDataLog::IMPORT_TYPE_COMMISSION,
                'created_at' => time(),
                'updated_at' => time(),
                'file_ids' => json_encode($file_ids),
            ];
            ImportSalesDataLog::insert($logData);
        }
    }

    /**
     * 文件读取回调，上传ali_oss
     * @param UploadedFile $file
     * @return \App\Admin\Services\Excel\string上传文件处理回调|bool|int
     */
    public function fileCallback(UploadedFile $file)
    {
        return SourceFileService::uploadImage($file, UploadFile::POST_PATH_EXCEL_IMPORT, date('YmdHis') . '_' . $file->getClientOriginalName());
    }


    /**
     * 插入数据库前执行
     * @param $data
     * @param Response $response
     * @return mixed
     */
    public function beforeInsert($data, Response $response)
    {
        if (empty($data)) {
            return [];
        }
        // TODO: Change the autogenerated stub
        $info = ImportSalesReconciliation::where('period_number', $data['period_number'])
//            ->where('dep_code', $data['department_code'])
//            ->where('bank_shortcut', $data['bank_shortcut'])
//            ->where('product_id', $data['product_id'])
//            ->where('apply_id', $data['apply_id'])
//            ->where('org_code', $data['org_code'])
//            ->where('custom_name', $data['custom_name'])
//            ->where('loan_period', $data['loan_period'])
            ->where('trade_date', $data['trade_date'])
//            ->where('system', $data['system'])
            ->where('status', ImportSalesData::STATUS_FINANCIAL_TO_CONFIRM)
            ->first();
        if ($info) {
            $amount = $info->commission_amount + $data['commission'];
            $info->commission_amount = '' . $amount;
            $info->save();
            $data['import_sales_reconciliation_id'] = $info->id;
        } else {
            $id = ImportSalesReconciliation::insertGetId([
                'org_code' => $data['org_code'],// varchar(16) DEFAULT NULL COMMENT '所属组织',
                'dep_code' => $data['department_code'],// varchar(16) DEFAULT NULL COMMENT '所属部门',
                'bank_shortcut' => $data['bank_shortcut'],// varchar(5) DEFAULT NULL COMMENT '银行编号',
                'product_id' => $data['product_id'],// int(11) DEFAULT NULL COMMENT '产品id',
                'apply_id' => $data['apply_id'],// bigint(20) DEFAULT NULL COMMENT '申请书编号',
                'period_number' => $data['period_number'],// varchar(64) DEFAULT NULL COMMENT '银行编号',
                'custom_name' => $data['custom_name'],// varchar(32) DEFAULT NULL COMMENT '贷款信息的客户姓名',
                'loan_period' => $data['loan_period'],// int(11) DEFAULT NULL COMMENT '贷款期数',
                'trade_date' => $data['trade_date'],// int(11) DEFAULT NULL COMMENT '支用日期',
                'system' => $data['system'],// varchar(32) DEFAULT 'unlink' COMMENT '所属系统',
                'status' => ImportSalesData::STATUS_FINANCIAL_TO_CONFIRM,// tinyint(3) DEFAULT NULL COMMENT '当前记录状态：1：待财务确认；2 ：财务已确认；3：已出纳',
                'trade_amount' => 0,// varchar(32) DEFAULT NULL COMMENT '支用金额',
                'commission_amount' => $data['commission'],// varchar(32) DEFAULT NULL COMMENT '返佣金额',
                'created_at' => time(),// int(11) DEFAULT NULL,
                'updated_at' => time(),// int(11) DEFAULT NULL,
            ]);
            $data['import_sales_reconciliation_id'] = $id;
        }

        return $data;
    }


}