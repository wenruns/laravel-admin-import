<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/7/9
 * Time: 17:22
 * maatwebsite v ~2.1.0
 */

namespace App\Admin\Services\Excel;


use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExcelProvider
{
    const NOT_SET_TABEL_EXCEPTION = 'not_set_table';  // 数据表异常信息

    const DATA_SAVE_FAILED_CODE = 500;  // 数据保存数据库失败
    const DATA_SAVE_SUCCESS_CODE = 200; // 数据保存数据库成功
    const DATA_SAVE_CUSTOMER_CODE = 300; // 用户手动选择导入数据
    const FILE_SUFFIX_ERROR_CODE = 400; // 文件格式错误

    /**
     * 导入数据集合
     * @var array
     */
    protected $_data = [];

    /**
     * 导入数据处理服务器
     * @var ExcelService|null
     */
    protected $_excelService = null;

    /**
     * 响应对象
     * @var Response|null
     */
    protected $_response = null;


    /**
     * 异常信息列表
     * @var array
     */
    protected static $_exceptions = [];

    /***
     * 语言
     * @var string
     */
    protected static $_lang = 'zh-CN';


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


    /**
     * 执行格式化数据
     * @param $data
     * @return array|bool
     */
    protected function dataProcessing($data)
    {
        $this->_excelService->initData()
            ->format($data)
            ->insertWithErrorData()
            ->insertWithExistData();
        if (!empty($normalData = $this->_excelService->getNormalData())) {
            $this->_response->setSuccessData($normalData, true);
        }
        if (!empty($errData = $this->_excelService->getErrorData())) {
            $this->_response->setErrorData($errData, true);
        }
        if (!empty($existData = $this->_excelService->getAbnormalData())) {
            $this->_response->setExistData($existData, true);
        }
        return $normalData;
    }


    /**
     * 保存数据——第一次导入请求触发
     * @throws \Exception
     */
    public function saveAll()
    {
        if (empty($this->_response->getFilesInfo())) {
            return;
        }
        if (empty($this->_data) || count($this->_data) == count($this->_data, 1)) {
            $this->_response->setResult([
                'status' => false,
                'code' => self::DATA_SAVE_FAILED_CODE,
                'errMsg' => '导入文件数据格式出错，数据解析失败！',
                'res' => [],
            ]);
            return;
        }
        $data = $this->_excelService->beforeDataProcess($this->_data);
        DB::beginTransaction();
        try {
            foreach ($data as $key => $item) {
                if (empty(array_filter($item, function ($value) {
                    return trim($value);
                }))) {
                    break;
                }
                $item = $this->_excelService->beforeInsert($this->dataProcessing($item), $this->_response);
                if (empty($item)) {
                    continue;
                }
                $item[$this->_excelService->model()->getKeyName()] = $this->_excelService->model()->insertGetId($item);
                $this->_excelService->afterInsert($item[$this->_excelService->model()->getKeyName()], $item, $this->_response);
                $data[$key] = $item;
            }
            $this->_excelService->afterDataProcess($this->_response);
            if ($this->_excelService->checkCommit()) {
                $this->_response->setResult([
                    'status' => true,
                    'code' => self::DATA_SAVE_CUSTOMER_CODE,
                    'errMsg' => 'Choose the data to insert by customer!',
                    'data' => $data,
                ]);
            } else {
                $this->_response->setResult([
                    'status' => true,
                    'code' => self::DATA_SAVE_SUCCESS_CODE,
                    'errMsg' => 'ok',
                    'data' => $data,
                ]);
                $this->_excelService->successCallback($this->_response);
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->_response->setErrorData($this->_response->getSuccessData(), true)
                ->setSuccessData([])
                ->setResult([
                    'status' => false,
                    'code' => self::DATA_SAVE_FAILED_CODE,
                    'errMsg' => $e->getMessage(),
                    'data' => $data,
                ]);
            $this->_excelService->failCallback($this->_response, $e);
        }
    }

    /**
     * 保存数据——客户选择性导入（第二次导入请求触发，在第一次请求的基础上）
     */
    public function saveAllByCustomer()
    {
        $data = $this->_excelService->_response->getSuccessData();
        DB::beginTransaction();
        try {
            foreach ($data as $key => $item) {
                $item = $this->_excelService->beforeInsert($item, $this->_response);
                $item[$this->_excelService->model()->getKeyName()] = $this->_excelService->model()->insertGetId($item);
                $this->_excelService->afterInsert($item[$this->_excelService->model()->getKeyName()], $item, $this->_response);
                $data[$key] = $item;
            }
            $this->_response->setResult([
                'status' => true,
                'code' => self::DATA_SAVE_SUCCESS_CODE,
                'errMsg' => 'ok',
                'data' => $data,
            ]);
            $this->_excelService->successCallback($this->_response);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->_response->setErrorData($this->_response->getSuccessData(), true)
                ->setSuccessData([])
                ->setResult([
                    'status' => false,
                    'code' => self::DATA_SAVE_FAILED_CODE,
                    'errMsg' => $e->getMessage(),
                    'data' => $data,
                ]);
            $this->_excelService->failCallback($this->_response, $e);
        }

    }

    /**
     * 设置语言
     * @param $lang
     */
    public static function setLang($lang)
    {
        self::$_lang = $lang;
    }

    /**
     * 抛出异常
     * @param $message
     * @param $lang
     * @throws \Exception
     */
    public static function throwException($message)
    {
        if (empty(self::$_exceptions) && is_file(__DIR__ . '/lang/' . self::$_lang . '.php')) {
            self::$_exceptions = require(__DIR__ . '/lang/' . self::$_lang . '.php');
        }
        if (isset(self::$_exceptions[$message])) {
            throw new \Exception(self::$_exceptions[$message]);
        } else {
            throw new \Exception($message);
        }
    }
}