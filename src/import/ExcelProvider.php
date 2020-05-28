<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/7/9
 * Time: 17:22
 * maatwebsite v ~2.1.0
 */

namespace Wenruns\Excel\import;


use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExcelProvider
{
    const NOT_SET_TABEL_EXCEPTION = 'not_set_table';  // 数据表异常信息

    const DATA_SAVE_FAILED_CODE = 500;  // 数据保存数据库失败
    const DATA_SAVE_SUCCESS_CODE = 200; // 数据保存数据库成功
    const DATA_SAVE_CUSTOMER_CODE = 300; // 用户手动选择导入数据

    protected $_data = [];  // 导入数据集合
    protected $_excelService = null;

    protected $_response = null;

    public function __construct(ExcelService $excelService)
    {
        $this->_excelService = $excelService;
        $this->_response = $excelService->_response;
    }

    /**
     * 获取excel文件的数据
     * @param string $file_path
     * @return $this
     */
    public function explainFile(string $file_path)
    {
        try {
            $data = Excel::load($file_path)->all();
        } catch (\Exception $e) {
            env('APP_DEBUG', false) && dd('app_debug', $e);
        }
        foreach ($data->toArray() as $sheet) {
            if (empty($sheet)) {
                continue;
            }
            if (!isset($sheet[0])) {
                $this->_data = array_merge($this->_data, $data->toArray());
                break;
            }
            $this->_data = array_merge($this->_data, $sheet);
        }
        return $this;
    }

//    protected function


    /**
     * 执行格式化数据
     * @param $data
     * @return array|bool
     */
    protected function dataProcessing($data)
    {
        $res = $this->_excelService->format($data);

        if ($res === false) {
            return false;
        }
        !isset($res['data']) && $res['data'] = [];
        !isset($res['errData']) && $res['errData'] = [];
        !isset($res['existData']) && $res['existData'] = [];


        if ($this->_excelService->insertWithErrorData()) {
            $res['data'] = array_merge($res['data'], $res['errData']);
            $res['errData'] = [];
        }
        if ($this->_excelService->insertWithExistData()) {
            $res['data'] = array_merge($res['data'], $res['existData']);
            $res['existData'] = [];
        }
        if (!empty($res['data'])) {
            $this->_response->setSuccessData($res['data'], true);
        }
        if (!empty($res['errData'])) {
            $this->_response->setErrorData($res['errData'], true);
        }
        if (!empty($res['existData'])) {
            $this->_response->setExistData($res['existData'], true);
        }
        return $res['data'];
    }


    /**
     * 保存数据
     * @throws \Exception
     */
    public function saveAll()
    {

        if (empty($this->_data)) {
            return;
        }
        if (count($this->_data) == count($this->_data, 1)) {
            $this->_response->setResult(
                $this->_excelService->makeResponse([
                    'status' => true,
                    'code' => self::DATA_SAVE_CUSTOMER_CODE,
                    'errMsg' => '导入文件格式出错，只有一维数组',
                    'res' => [],
                ]));
            return;
        }
        DB::beginTransaction();
        try {
            $res = [];
            method_exists($this->_excelService, 'beforeInsert') && $this->_excelService->beforeInsert($this->_response);
            foreach ($this->_data as $key => $item) {
                $item = $this->dataProcessing($item);
                if (!empty($item)) {
                    $res[] = $this->_excelService->model()->insertGetId($item);
                }
                if ($item === false) {
                    break;
                }
            }
            if (!$this->_excelService->checkCommit()) {
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            if (!empty($this->_response->getSuccessData())) {
                $this->_response->setErrorData($this->_response->getSuccessData(), true);
            }
            $this->_response->setSuccessData([])
                ->setResult(
                    $this->_excelService->makeResponse([
                        'status' => false,
                        'code' => self::DATA_SAVE_FAILED_CODE,
                        'errMsg' => $e->getMessage(),
                        'res' => [],
                    ]));
            method_exists($this->_excelService, 'failCallback') && $this->_excelService->failCallback($this->_response);
            return;
        }
        if ($this->_excelService->checkCommit()) {
            $this->_response->setResult(
                $this->_excelService->makeResponse([
                    'status' => true,
                    'code' => self::DATA_SAVE_CUSTOMER_CODE,
                    'errMsg' => 'Choose the data to insert by customer!',
                    'res' => [],
                ]));
        } else {
            $this->_response->setResult(
                $this->_excelService->makeResponse([
                    'status' => true,
                    'code' => self::DATA_SAVE_SUCCESS_CODE,
                    'errMsg' => 'ok',
                    'res' => $res,
                ]));
            method_exists($this->_excelService, 'successCallback') && $this->_excelService->successCallback($this->_response);
        }

    }

    public function saveAllByCustomer()
    {
        $data = $this->_excelService->_response->getSuccessData();
        DB::beginTransaction();
        try {
            $res = [];
            method_exists($this->_excelService, 'beforeInsert') && $this->_excelService->beforeInsert($this->_response);
            foreach ($data as $key => $item) {
                $res[] = $this->_excelService->model()->insertGetId($item);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->_response->setErrorData($this->_response->getSuccessData(), true)
                ->setSuccessData([])
                ->setResult(
                    $this->_excelService->makeResponse([
                        'status' => false,
                        'code' => self::DATA_SAVE_FAILED_CODE,
                        'errMsg' => $e->getMessage(),
                        'res' => [],
                    ]));
            method_exists($this->_excelService, 'failCallback') && $this->_excelService->failCallback($this->_response);
            return;
        }
        $this->_response->setResult(
            $this->_excelService->makeResponse([
                'status' => true,
                'code' => self::DATA_SAVE_SUCCESS_CODE,
                'errMsg' => 'ok',
                'res' => $res,
            ]));
        method_exists($this->_excelService, 'successCallback') && $this->_excelService->successCallback($this->_response);
    }


    /**
     * 抛出异常
     * @param $message
     * @throws \Exception
     */
    public static function throwException($message)
    {
        if (!self::$_exceptions || empty(self::$_exceptions)) {
            self::$_exceptions = require(__DIR__ . 'src');
        }
        if (isset(self::$_exceptions[self::$_lang][$message])) {
            throw new \Exception(self::$_exceptions[self::$_lang][$message]);
        } else {
            throw new \Exception($message);
        }
    }
}