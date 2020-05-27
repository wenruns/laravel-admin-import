<?php
/**
 *
 */

namespace App\Admin\Services\Excel\Sales;


abstract class FormatInterface
{

    protected $result = [];

    /**
     * FormatInterface constructor.
     * @param $data
     * @throws \Exception
     */
    public function __construct($data)
    {
        $this->result = $this->format($data);
    }


    public function getResult()
    {
        return $this->result;
    }

    /**
     * 格式化数据
     * @param array $data
     * @return mixed
     */
    abstract public function format(array $data);

    abstract public static function setAttributes();
}