<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/7/10
 * Time: 14:09
 */

namespace App\Admin\Services\Excel;


class Response
{
    protected $successData = [];

    protected $errorData = [];

    protected $existData = [];

    protected $importResult = [];

    protected $filesInfo = [];

    private $customize = [];

    /**
     * 导入结果
     * @param $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->importResult = $result;
        return $this;
    }

    /**
     * 设置导入成功的数据
     * @param $data
     * @param bool $append
     * @return $this
     */
    public function setSuccessData($data, $append = false)
    {
        if (empty($data)) {
            $data = [];
        }
        $append ? $this->successData[] = $data : $this->successData = $data;
        return $this;
    }

    /**
     * 设置导入失败的数据
     * @param $data
     * @param bool $append
     * @return $this
     */
    public function setErrorData($data, $append = false)
    {
        if (empty($data)) {
            $data = [];
        }
        $append ? $this->errorData[] = $data : $this->errorData = $data;
        return $this;
    }

    /**
     * 设置重复的数据
     * @param $data
     * @param bool $append
     * @return $this
     */
    public function setExistData($data, $append = false)
    {
        if (empty($data)) {
            $data = [];
        }
        $append ? $this->existData[] = $data : $this->existData = $data;
        return $this;
    }

    public function setFilesInfo($data, $key = null, $append = false)
    {
        $key ? $this->filesInfo[$key] = $data : ($append ? $this->filesInfo[] = $data : $this->filesInfo = $data);
        return $this;
    }

    public function getSuccessData()
    {
        return $this->successData;
    }

    public function getErrorData()
    {
        return $this->errorData;
    }

    public function getExistData()
    {
        return $this->existData;
    }

    public function getResult()
    {
        return $this->importResult;
    }

    public function getFilesInfo()
    {
        return $this->filesInfo;
    }

    /**
     * 返回导入结果
     * @return array
     */
    public function response()
    {
        return [
            'result' => $this->importResult,
            'successData' => $this->successData,
            'errorData' => $this->errorData,
            'existData' => $this->existData,
        ];
    }


    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
    }

    public function __get($name)
    {
        // TODO: Implement __get() method.
    }

    public function __set($name, $value)
    {
        // TODO: Implement __set() method.
    }
}