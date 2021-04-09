<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/10/8
 * Time: 14:13
 */

namespace Wenruns\Import;


use Illuminate\Database\Eloquent\Model;

abstract class ExcelService
{
    protected $_excelProvider = null;

    public $_response = null;

    protected $_excelTypes = ['xlsx', 'xls'];

    protected $_model = null;

    protected $_insertWithExistData = false; // 允许导入重复数据

    protected $_insertWithErrorData = false; // 允许导入异常数据

    protected $_insertWithCustomerChoice = false; // 弹出提示用户选择是否导入

//    protected $_responseSessionIndex = 'wen_response_session';

    function __construct(Model $model)
    {
        // 初始化各项配置信息
        try {
            $this->_model = $model;
            $this->_response = new Response($this);
            $this->_excelProvider = new ExcelProvider($this);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . ' in file:' . $e->getFile() . ' at line:' . $e->getLine());
        }
    }

    /**
     * 获取当前模型
     *
     * @return Model|null
     */
    public function model()
    {
        return $this->_model;
    }


    public function checkCommit()
    {
        return $this->insertWithCustomerChoise()
            && ((!$this->insertWithErrorData()
                    && !empty($this->_response->getErrorData()))
                || (!$this->insertWithExistData()
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
     *
     * @return bool
     */
    public function insertWithErrorData()
    {
        return $this->_insertWithErrorData;
    }

    /**
     * 判断是否允许插入重复数据
     *
     * @return bool
     */
    public function insertWithExistData()
    {
        return $this->_insertWithExistData;
    }

    /**
     * 保存到数据库
     * @return array
     * @throws \Exception
     */
    public function saveAll()
    {
        $this->checkFiles();
        $this->_excelProvider->saveAll();
        return $this->_response->response();
    }

    public function saveAllByCustomer(Response $response)
    {
        $this->_response->setSuccessData($response->getSuccessData())
            ->setErrorData($response->getErrorData())
            ->setFilesInfo($response->getFilesInfo())
            ->setExistData($response->getExistData());
        $this->_excelProvider->saveAllByCustomer();
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
                continue;
            }
            $info = [
                'fileName' => $file->getClientOriginalName(),
                'fileType' => $type,
                'filePath' => $file->getRealPath(),
            ];
            $this->_response->setFilesInfo($info, $key);
            $this->fileCallback($file, $info);
        }
        $this->Excel();
    }


    /**
     * 上传文件处理回调
     * @param $file
     * @param $info
     */
    public function fileCallback($file, $info)
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
     * @return mixed
     */
    abstract public function format($data);

    /**
     * @return array
     */
    abstract public function header();


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
     *
     * @param Response $response
     */
    public function beforeInsert(Response $response)
    {
        // todo::
    }

    /**
     * 导入失败回调
     * @param Response $response
     */
    public function failCallback(Response $response)
    {
        // todo:: 失败提示

    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        throw new \Exception('call the undefined method ' . $name);
    }


    protected $_response_session_index = 'wen_response_session';

    protected $_session_prefix = '';

    public function setSessionPrefix($prefix)
    {
        $this->_session_prefix = $prefix;
        return $this;
    }

    public function saveResponseToSession()
    {
        $index = $this->_session_prefix . $this->_response_session_index;
        return session([$index => serialize($this->_response)]);
    }

    /**
     * @return mixed
     */
    public function getResponseFromSession()
    {
        $index = $this->_session_prefix . $this->_response_session_index;
        $response = unserialize(session($index));
        session([$index => '']);
        return $response;
    }


    protected $_url = '';

    public function url($url)
    {
        $this->_url = $url;
        return $this;
    }

    /**
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


    protected $_err_header = [];

    public function errHeader($errHeader)
    {
        $this->_err_header = $errHeader;
        return $this;
    }

    public function getErrHeader()
    {
        if (empty($this->_err_header)) {
            $i = 0;
            foreach ($this->header() as $field => $title) {
                $this->_err_header[$field] = $title;
                $i++;
                if ($i > 2) {
                    break;
                }
            }
        }
        return $this->_err_header;
    }
}