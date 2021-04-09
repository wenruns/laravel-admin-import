<?php

namespace App\Admin\Services\Excel\Server;

use App\Admin\Services\Excel\ExcelService;
use App\Admin\Services\Excel\Response;
use App\Models\FaceSign;
use App\Models\ImportSalesData;
use App\Models\ImportSalesDataLog;
use App\Models\ImportSalesReconciliation;
use App\Models\MakeLoan;
use App\Models\Tuangou\LoanView;
use App\Models\UploadFile;
use App\Services\Common\SourceFileService;
use Encore\Admin\Facades\Admin;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class ImportService extends ExcelService
{

    protected $_date_to_time = [
        'trade_date',
    ];

    protected $_reconciliation_data = [];


    protected $checkEmpty = [
        'custom_name' => '姓名不能为空',
        'period_number' => '银行贷款编码不能为空',
        'trade_amount' => '支用累计不能为空',
        'trade_date' => '支用月份不能为空'
    ];

    protected $_file_ids = '';


    /**
     * 格式化xlsx数据
     * @param $data
     * @return ExcelService
     * @throws \Exception
     */
    public function format($data): ExcelService
    {
        // TODO: Implement format() method.
        if (empty($data)) {
            return $this;
        }
        $empty = true;
        $errMsg = '';
        foreach ($this->header() as $field => $title) {
            if (!array_key_exists($title, $data)) {
                throw new \Exception('以下名称发生错误：《' . $title . '》。请严格按照模板文件填写！<a href="' . route('admin.bank_sale.downloadTpl') . '" target="_blank" style="font-size: .8em;line-height: 30px;">点我下载模板文件</a>');
            }
            $item = trim($data[$title]);
            $item = mb_ereg_replace('(　| | )+', '', $item);

            if ($empty && !empty($item)) {
                $empty = false;
            }
            if (in_array($field, $this->_date_to_time)) {
                $timeStamp = strtotime($item);
                if ($timeStamp == false || $timeStamp < 0) {
                    $errMsg = '导入支用月份格式错误，请确认支用月份格式为：xxxx年xx月';
                }
                $item = strtotime(date('Y-m', $timeStamp));
            }
            $value[$field] = $item;
        }
        if (!isset($value) || $empty) {
            return $this;
        }
        if ($errMsg) {
            throw new \Exception($errMsg);
        }
        return $this->appendInfo($value);
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
            'trade_amount' => '支用累计',
            'trade_date' => '支用月份',
        ];
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    protected function formatData($data)
    {
        // 唯一标志
        $data['unique'] = md5(trim($data['period_number']) . ($data['trade_amount'] + 0) . $data['trade_date']);

        $data['apply_id'] = null;
        $data['org_code'] = null;
        $data['department_code'] = null;
        $data['product_id'] = null;
        $data['bank_shortcut'] = null;
        $data['loan_period'] = null;
        $data['system'] = ImportSalesData::SYSTEM_UNLINK;
        $data['err_msg'] = '正常数据';
        $data['created_at'] = time();
        $data['updated_at'] = time();
        return $data;
    }

    /**
     * @param $data
     * @return array|void
     */
    public function appendInfo($data)
    {
        if (empty($data)) {
            return $this;
        }
        $data = $this->formatData($data);
        $db_had_exist = false;
        $keyName = 'data';
        if ($info = ImportSalesData::where('unique', $data['unique'])->whereNull('deleted_at')->first()) {
            $db_had_exist = true;
            $keyName = 'existData';
        }
        $infos = FaceSign::where('face_sign.bank_loan_id', $data['period_number'])
            ->leftJoin('loan_form', 'loan_form.apply_id', '=', 'face_sign.apply_id')
            ->leftJoin('bank', 'bank.bank_id', '=', 'loan_form.bank_id')
            ->select(['face_sign.*', 'loan_form.loan_period', 'bank.shortcut'])
            ->first();      //取消客户姓名匹配
        if ($infos) {
            //取系统中客户姓名替代
            $data['custom_name'] = $infos->name;
            // 获取申请信息
            $data['org_code'] = $infos->org_code;
            $data['department_code'] = $infos->department_code;
            $data['loan_period'] = $infos->loan_period;
            $data['product_id'] = $infos->product_id;
            $data['apply_id'] = $infos->apply_id;
            $data['system'] = ImportSalesData::SYSTEM_BKQW_LOAN; // 查询到客户信息，标志为新系统
            $data['bank_shortcut'] = $infos->shortcut;
        } else {
            // 从旧系统中检测数据
            $infos = LoanView::where('ccb_apply_no', $data['period_number'])->first();
            if ($infos) {
                $org_code = [
                    '2' => 'D001',
                    '3' => 'D015',
                    '10' => 'D002',
                    '11' => 'D003',
                    '4' => 'D013',
                    '12' => 'D017',
                    '13' => 'D019',
                ];
                $data['system'] = ImportSalesData::SYSTEM_TTTUANGOU;
                $data['apply_id'] = $infos->id;
                $data['custom_name'] = $infos->host_name;
                $data['org_code'] = $org_code[$infos->cityid] ?? $infos->cityid;
                $data['department_code'] = '';
                $data['bank_shortcut'] = $infos->shortcut;
                $data['loan_period'] = $infos->loan_periods;
            } else {
                $data['org_code'] = Admin::user()->org_code;
                $data['department_code'] = Admin::user()->department_code;
                $data['err_msg'] = $db_had_exist ? '数据库已存在' : '未找到该客户的贷款信息';
                $keyName == 'data' ? $keyName = 'errData' : '';
            }
        }
        if (isset($data['apply_id'])) {
            $loan_money = MakeLoan::where('apply_id', $data['apply_id'])->sum('make_loan_money');
            if (empty($loan_money) || $loan_money <= 0) {
                $data['err_msg'] = $db_had_exist ? '数据库已存在' : '该笔贷款尚未放款';
                $keyName == 'data' ? $keyName = 'errData' : '';
            }
        }

        foreach ($this->checkEmpty as $index => $errMsg) {
            if (empty($data[$index])) {
                $data['err_msg'] = $errMsg;
                $keyName == 'existData';
                break;
            }
        }
        switch ($keyName) {
            case 'data':
                $this->setNormalData($data);
                break;
            case 'errData':
                $this->setErrorData($data);
                break;
            case 'existData':
                $this->setAbnormalData($data);
                break;
            default:
        }

        return $this;
    }


    /**
     * @return string
     */
    public function exportFormat()
    {
//        return parent::exportFormat(); // TODO: Change the autogenerated stub
        return <<<SCRIPT
            function(data, field){
                switch(field){
                    case 'period_number':
                        data = "'" + data[field];
                        break;
                    case 'trade_date':
                        if(Number(data[field])){
                            let date = new Date(data[field] * 1000);
                            let Y = date.getFullYear();
                            let M = date.getMonth() + 1;
                            data = "'" + Y + '-' + (M < 10 ? '0' + M : M);
                        }else{
                            data = "'" + data[field];
                        }
                        break;
                    default:  
                        data = data[field];  
                }
                return data;
            }
SCRIPT;
    }


    public function failData($data): array
    {
        // TODO: Change the autogenerated stub
        !empty($data['trade_date']) ? $data['trade_date'] = date('Y年m月', $data['trade_date']) : '';
        !empty($data['import_date']) ? $data['import_date'] = date('Y-m-d', $data['import_date']) : '';
        return $data;
    }

    /**
     * 导入数据成功回调，保存导入日志
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
                'type' => ImportSalesDataLog::IMPORT_TYPE_SALES,
                'created_at' => time(),
                'updated_at' => time(),
                'file_ids' => json_encode($file_ids),
            ];
            ImportSalesDataLog::insert($logData);
        }
    }


    /**
     * 上传ali_oss
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
//            ->where('org_code', $data['org_code'])
//            ->where('dep_code', $data['department_code'])
//            ->where('bank_shortcut', $data['bank_shortcut'])
//            ->where('product_id', $data['product_id'])
//            ->where('apply_id', $data['apply_id'])
//            ->where('custom_name', $data['custom_name'])
//            ->where('loan_period', $data['loan_period'])
            ->where('trade_date', $data['trade_date'])
//            ->where('system', $data['system'])
            ->where('status', ImportSalesData::STATUS_FINANCIAL_TO_CONFIRM)
            ->first();
        if ($info) {
            $amount = $info->trade_amount + $data['trade_amount'];
            $info->trade_amount = '' . $amount;
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
                'trade_amount' => $data['trade_amount'],// varchar(32) DEFAULT NULL COMMENT '支用金额',
                'commission_amount' => 0,// varchar(32) DEFAULT NULL COMMENT '返佣金额',
                'created_at' => time(),// int(11) DEFAULT NULL,
                'updated_at' => time(),// int(11) DEFAULT NULL,
            ]);
            $data['import_sales_reconciliation_id'] = $id;
        }

        return $data;
    }


}