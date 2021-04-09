<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/10/8
 * Time: 14:13
 */

namespace App\Admin\Services\Excel;


use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class ExcelService
{
    /**
     * excel工作提供者
     * @var ExcelProvider|null
     */
    protected $_excelProvider = null;

    /**
     * 响应处理类
     * @var Response|null
     */
    public $_response = null;

    /**
     * 允许导入类型
     * @var array
     */
    protected $_excelTypes = ['xlsx', 'xls'];

    /**
     * 模型
     * @var Model|null
     */
    protected $_model = null;

    /**
     * 是否允许导入重复数据
     * @var bool
     */
    protected $_insertWithExistData = false;

    /**
     * 是否允许导入异常数据
     * @var bool
     */
    protected $_insertWithErrorData = false;

    /**
     * 是否弹出提示用户选择是否导入
     * @var bool
     */
    protected $_insertWithCustomerChoice = false;

    /**
     * 弹出提示用户选择是否导入的前提下，缓存response响应类的索引
     * @var string
     */
    protected $_response_session_index = 'wen_response_session';

    /**
     * 缓存response响应类的索引前缀
     * @var string
     */
    protected $_session_prefix = '';

    /**
     * 正常数据
     * @var array
     */
    protected $_normalData = [];

    /**
     * 异常数据
     * @var array
     */
    protected $_abnormalData = [];

    /**
     * 错误数据
     * @var array
     */
    protected $_errorData = [];


    /**
     * 失败数据列表显示字段
     * @var array
     */
    protected $_errHeader = [];

    /**
     * ExcelService constructor.
     * @param Model $model
     * @throws \Exception
     */
    function __construct(Model $model)
    {
        // 初始化各项配置信息
        try {
            $this->_model = $model;
            $this->_response = new Response($this);
            $this->_excelProvider = new ExcelProvider($this);
            $this->initData();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . ' in file:' . $e->getFile() . ' at line:' . $e->getLine());
        }
    }

    /**
     * 初始化数据
     * @return $this
     */
    public function initData()
    {
        $this->_abnormalData = [];
        $this->_normalData = [];
        $this->_errorData = [];
        return $this;
    }

    /**
     * 设置错误数据
     * @param array $data
     * @return $this
     */
    public function setErrorData(array $data)
    {
        $this->_errorData = $data;
        return $this;
    }

    /**
     * 设置正常数据
     * @param array $data
     * @return $this
     */
    public function setNormalData(array $data)
    {
        $this->_normalData = $data;
        return $this;
    }


    /**
     * 设置异常数据
     * @param array $data
     * @return $this
     */
    public function setAbnormalData(array $data)
    {
        $this->_abnormalData = $data;
        return $this;
    }

    /**
     * 获取异常数据
     * @return array
     */
    public function getAbnormalData()
    {
        return $this->_abnormalData;
    }

    /**
     * 获取正常数据
     * @return array
     */
    public function getNormalData()
    {
        return $this->_normalData;
    }

    /**
     * 获取错误数据
     * @return array
     */
    public function getErrorData()
    {
        return $this->_errorData;
    }

    /**
     * 获取当前模型
     * @return Model|null
     */
    public function model()
    {
        return $this->_model;
    }

    /**
     * 检测提交方式，是否启用“用户选择保存数据”功能
     * @return bool
     */
    public function checkCommit()
    {
        return $this->insertWithCustomerChoise()
            && ((!$this->insertWithErrorData(true)
                    && !empty($this->_response->getErrorData()))
                || (!$this->insertWithExistData(true)
                    && !empty($this->_response->getExistData())));
    }


    /**
     * 允许插入错误数据
     * @param bool $enable
     * @return $this
     */
    public function enableInsertWithErrorData($enable = true)
    {
        $this->_insertWithErrorData = $enable;
        return $this;
    }


    /**
     * 获取导入文件的信息
     * @return array
     */
    public function getExcelFiles()
    {
        return $this->_response->getFilesInfo();
    }


    /**
     * 允许插入重复数据
     *
     * @param bool $enable
     * @return $this
     */
    public function enableInsertWithExistData($enable = true)
    {
        $this->_insertWithExistData = $enable;

        return $this;
    }


    /**
     * 弹出提示用户
     *
     * @param bool $enable
     * @return $this
     */
    public function enableInsertWithCustomerChoice($enable = true)
    {
        $this->_insertWithCustomerChoice = $enable;

        return $this;
    }

    /**
     * 客户自定义选择导入内容
     * @return bool
     */
    public function insertWithCustomerChoise()
    {
        return $this->_insertWithCustomerChoice;
    }

    /**
     * 判断是否允许插入错误数据
     * @param bool $isBool
     * @return $this|bool
     */
    public function insertWithErrorData($isBool = false)
    {
        if ($isBool) {
            return $this->_insertWithErrorData;
        }
        if ($this->_insertWithErrorData && !empty($this->_errorData)) {
            $this->_normalData = array_merge($this->_normalData, $this->_errorData);
            $this->_errorData = [];
        }
        return $this;
    }

    /**
     * 判断是否允许插入重复数据
     * @param bool $isBool
     * @return $this|bool
     */
    public function insertWithExistData($isBool = false)
    {
        if ($isBool) {
            return $this->_insertWithExistData;
        }
        if ($this->_insertWithExistData && !empty($this->_abnormalData)) {
            $this->_normalData = array_merge($this->_normalData, $this->_abnormalData);
            $this->_abnormalData = [];
        }
        return $this;
    }

    /**
     * 保存到数据库
     * @return array
     * @throws \Exception
     */
    public function saveAll()
    {
        try {
            $this->checkFiles();
            $this->_excelProvider->saveAll();
        } catch (\Exception $e) {
            $this->_response->setResult([
                'status' => false,
                'code' => ExcelProvider::DATA_SAVE_FAILED_CODE,
                'errMsg' => $e->getMessage(),
                'data' => [],
            ]);
        }
        return $this->_response->response();
    }

    public function saveAllByCustomer(Response $response)
    {
        try {
            $this->_response->setSuccessData($response->getSuccessData())
                ->setErrorData($response->getErrorData())
                ->setFilesInfo($response->getFilesInfo())
                ->setExistData($response->getExistData());
            $this->_excelProvider->saveAllByCustomer();
        } catch (\Exception $e) {
            $this->_response->setResult([
                'status' => false,
                'code' => ExcelProvider::DATA_SAVE_FAILED_CODE,
                'errMsg' => $e->getMessage(),
                'data' => [],
            ]);
        }
        return $this->_response->response();
    }


    /**
     * 检测是否上传文件
     */
    public function checkFiles()
    {
        $files = \request()->files->all();
        if (empty($files)) {
            return;
        }
        $this->checkFilesType($files);
    }

    /**
     * 判断删除的文件格式是否正确
     * @param $files
     */
    public function checkFilesType($files)
    {
        foreach ($files as $key => $file) {
            // 获取文件类型
            $type = substr($file->getClientOriginalName(), strripos($file->getClientOriginalName(), '.') + 1);
            // 判断是否为允许导入的文件类型
            if (!in_array($type, $this->_excelTypes)) {
                // todo:: 文件格式错误处理
                throw new \Exception('文件格式错误');
            }
            $info = [
                'fileName' => $file->getClientOriginalName(),
                'fileType' => $type,
                'filePath' => $file->getRealPath(),
                'data' => $this->fileCallback($file)
            ];
            $this->_response->setFilesInfo($info, $key);
        }
        $this->Excel();
    }


    /**
     * @param $file
     * @param $info
     * @param $response
     * @return string上传文件处理回调
     */
    public function fileCallback(UploadedFile $file)
    {
        return '';
    }

    /**
     * 在执行数据解析程序之前的操作
     * @param $data
     * @return mixed
     */
    public function beforeDataProcess($data)
    {
        return $data;
    }

    /**
     * @param Response $response
     */
    public function afterDataProcess(Response $response)
    {

    }

    /**
     * 读取excel文件
     */
    public function Excel()
    {
        if (empty($this->_response->getFilesInfo())) {
            return;
        }
        try {
            // 判断是存在导入文件
            foreach ($this->_response->getFilesInfo() as $key => $item) {
                $this->_excelProvider->explainFile($item['filePath']);
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * 导入数据格式化
     * @param $data
     * @return ExcelService
     */
    abstract public function format($data): ExcelService;

    /**
     * @return array
     */
    abstract public static function header();


    /**
     * 导出数据格式化处理
     * @return string
     */
    public function exportFormat()
    {
        return <<<SCRIPT
            function(data, field){
                field = field.split('.');
                field.forEach(function(item, dex){
                    data = data[item];
                })
                return data;
            }
SCRIPT;
    }

    /**
     * 页面显示错误的数据处理
     * @param $data
     * @return array
     */
    public function failData($data): array
    {
        return $data;
    }


    /**
     * 导入数据库成功回调
     * @param Response $response
     */
    public function successCallback(Response $response)
    {
        // todo:: 成功提示
    }

    /**
     * 插入数据库前钩子方法
     * @param $data
     * @param Response $response
     * @return mixed
     */
    public function beforeInsert($data, Response $response)
    {
        // todo::插入数据库前的操作
        return $data;
    }

    /**
     * 插入数据库后的构造
     * @param $id
     * @param $data
     * @param Response $response
     */
    public function afterInsert($id, $data, Response $response)
    {
        // todo::插入数据库后的操作
    }

    /**
     * @param Response $response
     * @param \Exception|null $e
     */
    public function failCallback(Response $response, \Exception $e = null)
    {
        // todo:: 失败提示
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        throw new \Exception('call the undefined method ' . $name);
    }


    /**
     * 获取response缓存的路径
     * @return string
     */
    protected function getResponseCacheIndex()
    {
        return $this->_session_prefix . $this->model()->getTable() . $this->_response_session_index;
    }

    /**
     * 设置session缓存索引前缀
     * @param $prefix
     * @return $this
     */
    public function setSessionPrefix($prefix)
    {
        $this->_session_prefix = $prefix;
        return $this;
    }

    /**
     * 缓存Response响应类
     * @return \Illuminate\Session\SessionManager|\Illuminate\Session\Store|mixed
     */
    public function saveResponseToSession()
    {
        $index = $this->getResponseCacheIndex();
        return session([$index => serialize($this->_response)]);
    }

    /**
     * 获取缓存的Response响应类
     * @return mixed
     */
    public function getResponseFromSession()
    {
        $index = $this->getResponseCacheIndex();
        $response = unserialize(session($index));
        session([$index => '']);
        return $response;
    }

    /**
     * 自定义导入、导出、删除url
     * @var string
     */
    protected $_url = '';

    /**
     * 自定义导入、导出、删除url
     * @param $url
     * @return $this
     */
    public function url($url)
    {
        $this->_url = $url;
        return $this;
    }

    /**
     * 生成导入、导出、删除的url
     * @param $params
     * @return string
     */
    public function makeUrl($params)
    {
        if (empty($this->_url)) {
            $url = \request()->getUri();
            if (strpos($url, 'op=') !== false) {
                $url = mb_substr($url, 0, strpos($url, 'op='));
            }
        } else {
            $url = $this->_url;
        }
        if (empty($params)) {
            return $url;
        }

        if (is_array($params)) {
            $http_params = http_build_query($params);
        } else {
            $http_params = $params;
        }
        if (strpos($url, '?') === false) {
            $url .= "?$http_params";
        } else {
            $url .= "&$http_params";
        }
        return $url;
    }


    /**
     * 设置导入失败的显示字段
     * @param $errHeader
     * @return $this
     */
    public function errHeader($errHeader)
    {
        $this->_errHeader = $errHeader;
        return $this;
    }

    /**
     * 获取导入失败显示的字段
     * @return array
     */
    public function getErrHeader()
    {
        if (empty($this->_errHeader)) {
            $this->_errHeader = $this->header();
        }
        return $this->_errHeader;
    }
}