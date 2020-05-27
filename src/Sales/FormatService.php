<?php

namespace App\Admin\Services\Excel\Sales;

use App\Admin\Services\Excel\Response;
use App\Models\Bank;
use App\Models\ImportSalesData;
use App\Models\LoanForm;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormatService extends FormatInterface
{
    protected $data = [];

    protected $errData = [];

    protected $existData = [];

    protected $arr = [];

    /**
     * 格式化数据
     * @param array $data
     * @return array|bool|mixed|void
     * @throws \Exception
     */
    public function format(array $data)
    {
        if (empty($data)) {
            return;
        }
        // TODO: Implement format() method.
        $attributes = array_flip(self::setAttributes());
        $date_to_int = self::setDateToTimeFields();
        $value = [];
        $empty = true;
        foreach ($data as $key => $item) {
            if (!isset($attributes[$key])) {
                throw new \Exception('以下名称发生错误：《' . $key . '》。请严格按照模板文件填写！<a href="' . route('admin.bank_sale.downloadTpl') . '" target="_blank" style="font-size: .8em;line-height: 30px;">点我下载模板文件</a>');
            }
            if ($empty && !empty(trim($item))) {
                $empty = false;
            }
            if (in_array($attributes[$key], $date_to_int)) {
                $item = strtotime(date('Y-m', strtotime($item)));
            }
            $value[$attributes[$key]] = $item;
        }
        if ($empty) {
            return false;
        }
        $this->appendInfo($value);
        return [
            'data' => $this->data,
            'errData' => $this->errData,
            'existData' => $this->existData
        ];
    }


    /**
     *
     * @param $data
     * @return mixed
     */
    protected function initData($data)
    {
        // 唯一标志
        $data['unique_sign'] = md5(json_encode($data));
        $data['trade_amount'] = '' . round($data['trade_amount'], 2);
        $data['import_date'] = time();
        $data['err_msg'] = '正常数据';
        $data['created_at'] = time();
        $data['updated_at'] = time();

        $data['apply_id'] = null;
        $data['org_code'] = null;
        $data['department_code'] = null;
        $data['product_id'] = null;
        $data['bank_shortcut'] = null;
        $data['loan_period'] = null;
        $data['bookkeeping_date'] = $data['trade_date'];
        $data['system'] = 'unlink';
        return $data;
    }

    protected $checkEmpty = [
        'custom_name' => '姓名不能为空',
        'period_number' => '银行贷款编码不能为空',
        'trade_amount' => '支用累计不能为空',
        'trade_date' => '支用月份不能为空'
    ];


    public function checkOldSystem($period_number)
    {
        //1.1消费数据关联更新到旧系统
        $connection = DB::connection('old_loan');
        $oldLoanDBName = $connection->getDatabaseName();
        $oldLoanTabkeName = $connection->getTablePrefix() . 'tttuangou_loan';
        $sql = 'SELECT `ctl`.`id`,`ctl`.`apply_periods` FROM `' . $oldLoanDBName . '`.`' . $oldLoanTabkeName . '` AS `ctl` WHERE `ctl`.`ccb_apply_no` = \'' . $period_number . '\' AND `ctl`.id is not null';
        dd($sql);
    }

    /**
     * @param $data
     */
    public function appendInfo($data)
    {
        if (empty($data)) {
            return;
        }
        $data = $this->initData($data);

        $db_had_exist = false;
        if ($info = ImportSalesData::where('unique_sign', $data['unique_sign'])->whereNull('deleted_at')->first()) {
            $data['import_date'] = strtotime($info->import_date);
            $db_had_exist = true;
        }

        $infos = LoanForm::where('bank_loan_id', $data['period_number'])->first();      //取消客户姓名匹配
        if (empty($infos)) {
//            // 检测旧系统，是否存在该贷款记录
//            $infos = $this->checkOldSystem($data['period_number']);
            $data['org_code'] = Admin::user()->org_code;
            $data['department_code'] = Admin::user()->department_code;
            $data['err_msg'] = $db_had_exist ? '数据库已存在' : '未找到该客户的贷款信息';
            $db_had_exist ? $this->existData = $data : $this->errData = $data;
            return;
        }
        //取系统中客户姓名替代
        $data['custom_name'] = $infos->name;
        // 获取申请信息
        $data['org_code'] = $infos->org_code;
        $data['department_code'] = $infos->department_code;
        $data['loan_period'] = $infos->loan_period;
        $data['product_id'] = $infos->product_id;
        $data['apply_id'] = $infos->apply_id;
        $data['bookkeeping_date'] = $data['trade_date'];
        $data['system'] = ImportSalesData::SYSTEM_BKQW_LOAN; // 查询到客户信息，标志为新系统
        // 获取银行标志号
        $bank_shortcut = Bank::where('bank_id', $infos->bank_id)->value('shortcut');
        if (!$bank_shortcut) {
            $data['err_msg'] = $db_had_exist ? '数据库已存在' : '未找到银行短标记';
            $db_had_exist ? $this->existData = $data : $this->errData = $data;
            return;
        }
        $data['bank_shortcut'] = $bank_shortcut;


        if (in_array($data['unique_sign'], $this->arr)) {
            $data['err_msg'] = $db_had_exist ? '数据库已存在' : 'excel表中存在相同数据';
            $this->existData = $data;
            return;
        }
        array_push($this->arr, $data['unique_sign']);

        foreach ($this->checkEmpty as $index => $errMsg) {
            if (empty($data[$index])) {
                $data['err_msg'] = $errMsg;
                $this->existData = $data;
                return;
            }
        }

        $this->data = $data;
    }


    public static function setDateToTimeFields()
    {
        return [
            'trade_date',
        ];
    }

    /**
     * @return array
     * 返回属性对应的标题
     */
    public static function setAttributes()
    {
        // TODO: Implement exportAttributes() method.
        return [
            'custom_name' => '姓名',
            'period_number' => '银行贷款编码',
            'trade_amount' => '支用累计',
            'trade_date' => '支用月份',
        ];
    }

}