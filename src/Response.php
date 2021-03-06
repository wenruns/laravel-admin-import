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

    protected $excelService = null;

    protected $successData = [];

    protected $errorData = [];

    protected $existData = [];

    protected $importResult = [];

    protected $filesInfo = [];

    protected $_n = 1;

    /**
     * @var \Closure
     */
    protected $showResultCallback = null;


    public function __construct(ExcelService $excelService)
    {
        $this->excelService = $excelService;
    }

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
        $this->showResult();
        return [
            'result' => $this->importResult,
            'successData' => $this->successData,
            'errorData' => $this->errorData,
            'existData' => $this->existData,
        ];
    }

    public function customizeShowResult(\Closure $closure)
    {
        $this->showResultCallback = $closure;
        return $this;
    }


    public function showResult()
    {
        if (empty($this->importResult)) {
            return;
        }
        switch ($this->importResult['code']) {
            case ExcelProvider::DATA_SAVE_CUSTOMER_CODE:
                $this->checkCustomerChoice();
                break;
            case ExcelProvider::DATA_SAVE_FAILED_CODE:
                if ($this->showResultCallback) {
                    $this->showResultCallback->call($this, $this->importResult);
                } else {
                    $this->fail();
                }
                break;
            case ExcelProvider::DATA_SAVE_SUCCESS_CODE:
                if ($this->showResultCallback) {
                    $this->showResultCallback->call($this, $this->importResult);
                } else {
                    $this->success();
                }
                break;
            default:
        }
    }


    public function success()
    {
        $success = count($this->getSuccessData());
        $fail = count($this->getErrorData());
        $repeat = count($this->getExistData());
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

    public function fail()
    {
        $result = $this->getResult();
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


    /**
     * 检测是否做出反应
     */
    protected function checkCustomerChoice()
    {
        $result = $this->getResult();
        if ($this->excelService->checkCommit() && $result['status']) {
            $this->excelService->saveResponseToSession();
            $content = $this->showCustomerHtml();
            $url = $this->excelService->makeUrl('op=import');
            (new ShowLayer([
                'title' => '数据监测结果',
                'content' => $content,
                'width' => '1000px',
                'confirmButtonText' => '继续导入',
                'cancelButtonText' => '取消导入',
            ]))->then("function(isConfirm){
                if(isConfirm.value){
                    let url = '{$url}';
                    let param = '';
                    document.querySelectorAll('.import-options').forEach(function(item, dex){
                        if (item.checked) {
                            param += item.value + '.';
                        }
                    });
                    url += '&types=' + param;
                    window.location.href = url;
                }
            }")->render();
        }
    }

    /**
     * 显示重复信息和异常信息
     * @return string
     */
    protected function showCustomerHtml()
    {
        $errData = $this->getErrorData();
        $existData = $this->getExistData();
        $data = $this->getSuccessData();
        $errCount = count($errData);
        $existCount = count($existData);
        $dataCount = count($data);
        $totalCount = $errCount + $existCount + $dataCount;

        $errPercent = round(($errCount / ($existCount + $errCount + $dataCount) * 10000) / 100, 3);
        $exitPercent = round(($existCount / ($existCount + $errCount + $dataCount) * 10000) / 100, 3);

        return <<<HTML
    <div id="wen-import-data">
        <style>
            #wen-import-data table{
                width:100%; 
                border:1px solid;
                font-size: 1em;
            }
            #wen-import-data td{
                border:1px solid #000000;
                padding: 5px;
            }
            #wen-import-data .table-list{
                height: 60vh;
                overflow: auto;
            }
        </style>
        <table>
            <tr>
                <td colspan="8">一共导入{$totalCount}条记录</td>
            </tr>
            <tr>
                {$this->abnormalInfo($errCount, $errPercent)}
                {$this->repeatInfo($existCount, $exitPercent)}
            </tr>
            <tr>
                <td colspan="2" style="border:1px solid #000000;padding: 5px; background: #FF8000;color: #FFFFFF;cursor: pointer;text-align: center; " class="wen-import-button" data-flag="normal">正常数据</td>
                {$this->abnormalButton()}
                {$this->repeatButton()}
            </tr>
            <tr>
                <td colspan="8">
                    <div class="table-list table-list-normal">
                        {$this->makeTable($data)}
                    </div>
                    {$this->abnormalTable($errData)}
                    {$this->repeatTable($existData)}
                </td>
            </tr>
            <tr>
                <td colspan="8" style="text-align: center">
                    <span><input class="import-options" type="checkbox" name="type[]" value="normal" checked disabled>正常数据</span>
                    {$this->abnormalCheckedBox()}
                    {$this->repeatCheckedBox()}
                </td>
            </tr>
        </table>
    </div>
    <script >
        let obj = document.querySelectorAll('.wen-import-button');
        obj.forEach(function(element, dex){
            element.addEventListener("click", function(e){
                obj.forEach(function(item){
                    item.style.background = "#FFD1A4";
                    item.style.color = "#000000";
                    item.setAttribute('colspan', {$this->_n});
                });
                e.target.style.background = "#FF8000";
                e.target.style.color = "#ffffff";
                e.target.setAttribute('colspan', 2);
                document.querySelectorAll(".table-list").forEach(function(item){
                    item.style.display = "none";
                })
                document.querySelector(".table-list-" + e.target.dataset.flag).style.display = "block";
            });
        });
    </script>
HTML;
    }


    /**
     * 判断是否允许插入重复数据
     * @return mixed
     */
    protected function checkRepeat()
    {
        return $this->excelService->insertWithExistData(true);
    }


    /**
     * 重复信息
     * @param $existCount
     * @param $exitPercent
     * @return string
     */
    protected function repeatInfo($existCount, $exitPercent)
    {
        if ($this->checkRepeat()) {
            return '';
        }
        return <<<HTML
    <td style="border:1px solid #000000;padding: 5px;width: 10%;">重复</td>
    <td style="border:1px solid #000000;padding: 5px;width: 15%;">{$existCount}条</td>
    <td style="border:1px solid #000000;padding: 5px;width: 10%;">重复率</td>
    <td style="border:1px solid #000000;padding: 5px;width: 15%;">{$exitPercent}%</td>
HTML;

    }

    /**
     * 重复按钮
     * @return string
     */
    protected function repeatButton()
    {
        if ($this->checkRepeat()) {
            return '';
        }
        $this->_n++;
        return <<<HTML
<td colspan="3" style="border:1px solid #000000;padding: 5px; cursor: pointer;text-align: center;background: #FFD1A4;color: #000000;" class="wen-import-button" data-flag="repeat">重复数据</td>
HTML;

    }

    /**
     * 重复数据复选框
     * @return string
     */
    protected function repeatCheckedBox()
    {
        if ($this->checkRepeat()) {
            return '';
        }
        return <<<HTML
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<span><input class="import-options" type="checkbox" name="type[]" value="repeat">重复数据</span>
HTML;

    }

    /**
     * 重复数据列表
     * @param $existData
     * @return string
     */
    protected function repeatTable($existData)
    {
        if ($this->checkRepeat()) {
            return '';
        }
        $tableExist = $this->makeTable($existData);
        return <<<HTML
<div class="table-list table-list-repeat" style="display: none;">
    {$tableExist}
</div>
HTML;

    }

    /**
     * 判断是否允许插入异常数据
     * @return mixed
     */
    protected function checkAbnormal()
    {
        return $this->excelService->insertWithErrorData(true);
    }

    /**
     * 异常信息
     * @param $errCount
     * @param $errPercent
     * @return string
     */
    protected function abnormalInfo($errCount, $errPercent)
    {
        if ($this->checkAbnormal()) {
            return '';
        }
        return <<<HTML
    <td style="border:1px solid #000000;padding: 5px;width: 10%;">异常</td>
    <td style="border:1px solid #000000;padding: 5px;width: 15%;">{$errCount}条</td>
    <td style="border:1px solid #000000;padding: 5px;width: 10%;">异常率</td>
    <td style="border:1px solid #000000;padding: 5px;width: 15%;">{$errPercent}%</td>
HTML;

    }

    /**
     * 异常按钮
     * @return string
     */
    protected function abnormalButton()
    {
        if ($this->checkAbnormal()) {
            return '';
        }
        $this->_n++;
        return <<<HTML
<td colspan="3" style="border:1px solid #000000;padding: 5px; background: #FFD1A4;color: #000000;cursor: pointer;text-align: center;" class="wen-import-button" data-flag="abnormal">异常数据</td>
HTML;

    }

    /**
     * 异常数据列表
     * @param $errData
     * @return string
     */
    protected function abnormalTable($errData)
    {
        if ($this->checkAbnormal()) {
            return '';
        }
        $tableError = $this->makeTable($errData);
        return <<<HTML
<div class="table-list table-list-abnormal" style="display: none;">
    {$tableError}
</div>
HTML;

    }


    /**
     * 异常数据复选框
     * @return string
     */
    protected function abnormalCheckedBox()
    {
        if ($this->checkAbnormal()) {
            return '';
        }
        return <<<HTML
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<span><input class="import-options" type="checkbox" name="type[]" value="abnormal">异常数据</span>
HTML;

    }

    /**
     * 生成数据列表
     * @param $data
     * @return string
     */
    protected function makeTable($data)
    {
        $key = mt_rand(0, 9999);
        $perPage = 20;
        $header = $this->excelService->getErrHeader();
//        $header = $this->excelService->header();

        $theadHtml = '';
        foreach ($header as $key => $title) {
            $theadHtml .= '<th>' . $title . '</th>';
        }
        $tbodyHtml = '';
        foreach ($data as $key => $item) {
            $item = $this->excelService->failData($item);
            if ($key < $perPage) {
                $tbodyHtml .= '<tr>';
                foreach ($header as $field => $title) {
                    if (!isset($item[$field])) {
                        $item[$field] = '';
                    }
                    $tbodyHtml .= '<td>' . $item[$field] . '</td>';
                }
                $tbodyHtml .= '</tr>';
            }
            $data[$key] = $item;
        }
        $colspan = count($header);
        $total = ceil(count($data) / $perPage);
        $n = 2;
        $m = 3;
        $borderValue = $m * 2 + $n + 1;
        $pageHtml = '';
        if ($total > $n * 2 + $m * 2 + 1) {
            $max = $m * 2 + $n * 2 + 1;
            $pageNumbers = range(1, $max);
            foreach ($pageNumbers as $i) {
                $class = '';
                if ($i > $max - $n) {
                    $pageNum = $total + $i - $max;
                } else {
                    $pageNum = $i;
                }
                if ($i == 1) {
                    $class = 'class="page-choose"';
                }
                $pageHtml .= "<li data-page='$pageNum' $class>$pageNum</li>";
                if ($i == $n) {
                    $pageHtml .= '<span class="hide-ellipsis">...</span>';
                } else if ($i == $max - $n) {
                    $pageHtml .= '<span class="ellipsis">...</span>';
                }
            }
        } else if ($total) {
            foreach (range(1, $total) as $i) {
                if ($i == 1) {
                    $pageHtml .= "<li data-page='$i' class='page-choose'>$i</li>";
                } else {
                    $pageHtml .= "<li data-page='$i'>$i</li>";
                }
            }
        }

        $data = json_encode($data);
        $header = json_encode($header);
        return <<<HTML
<style>
    .table-list-content-{$key}{
        width: 100%;
        font-size: 1em !important;
        border: 1px solid #000000;
    }
    .table-list-content-{$key} td,.table-list-content-{$key} th{
        text-align: center;
        border: 1px solid #000000;
        padding: 5px;
    }
   .table-list-content-{$key} .table-list-page{
        display: flex;
        padding: 0px;
        margin: 0px;
        justify-content: center;
        align-items: center;
   }
   .table-list-content-{$key} .table-list-page li{
        list-style: none;
        width: 30px;
        height: 30px;
        padding: 0px;
        margin: 0px;
        display: flex;
        justify-content: center;
        align-items: center;
        border: 1px solid #F0F0F0;
        cursor: pointer;
   }
   .table-list-content-{$key} .table-list-page li:hover{
        border: 1px solid #2894FF;
        background: #66B3FF;
   }
   .table-list-content-{$key} .page-choose{
        background: #66B3FF;
        border: 1px solid #2894FF !important;
   }
   .table-list-content-{$key} .table-list-page .hide-ellipsis, .table-list-content-{$key} .table-list-page .ellipsis{
        padding: 0px 5px;
   }
   .table-list-content-{$key} .table-list-page .hide-ellipsis{
        display: none;
   }
</style>
<table class="table-list-content-{$key}">
    <thead id="table-list-id-{$key}">
        <tr>
            {$theadHtml}
        </tr>    
    </thead>
    <tbody class="table-list-{$key}">
        {$tbodyHtml}
    </tbody>
    <tr>
        <td colspan="{$colspan}">
            <ul class="table-list-page">
                {$pageHtml}
            </ul>
            <a href="#table-list-id-{$key}" id="table-tbody-position-{$key}"></a>
        </td>
    </tr>
</table>
<script>
    var pages_{$key} = document.querySelectorAll('.table-list-content-{$key} .table-list-page li'),
        hideEllipsis_{$key} = document.querySelector('.table-list-content-{$key} .table-list-page .hide-ellipsis');
        endEllipsis_{$key} = document.querySelector('.table-list-content-{$key} .table-list-page .ellipsis');
    pages_{$key}.forEach(function(item, index) {
        item.addEventListener('click', function(e) {
            pages_{$key}.forEach(function(obj, dex) {
                obj.classList.remove('page-choose')
            });
            let page = Number(e.target.dataset.page),
                borderVal = {$borderValue},
                totalPage = {$total},
                pageM = {$m},
                pageN = {$n};
            if(hideEllipsis_{$key} && page + pageM >= totalPage - pageN){
                hideEllipsis_{$key}.style.display = 'block';
                endEllipsis_{$key}.style.display = 'none';
                let len = pageM * 2 + 1;
                for(var i = totalPage - pageN - len + 1, j = 0; j < len; i++, j++){
                    var k = j + 2;
                    pages_{$key}[k].setAttribute("data-page", i);
                    pages_{$key}[k].innerHTML = i;
                    if(i == page){
                        pages_{$key}[k].classList.add('page-choose');
                    }
                }
            } else if(hideEllipsis_{$key} && page >= borderVal){
                hideEllipsis_{$key}.style.display = 'block';
                endEllipsis_{$key}.style.display = 'block';
                for(var i = pageN, j = 0; j < (pageM * 2 + 1); i++, j++){
                    var pg = page- pageM + j;
                    pages_{$key}[i].setAttribute("data-page", pg);
                    pages_{$key}[i].innerHTML = pg;
                    if(pg == page){
                        pages_{$key}[i].classList.add('page-choose');
                    }
                }
            }else if(hideEllipsis_{$key} && page < borderVal){
                hideEllipsis_{$key}.style.display = 'none';
                endEllipsis_{$key}.style.display = 'block';
                for(var i = 1; i<= (pageM * 2 + pageN + 1); i++){
                    pages_{$key}[i-1].setAttribute("data-page", i);
                    pages_{$key}[i-1].innerHTML = i;
                    if(i == page){
                        pages_{$key}[i-1].classList.add('page-choose');
                    }
                }
            }else{
                e.target.classList.add('page-choose');                
            }
            makePageContent{$key}(page)
        })
    })
    
    function makePageContent{$key}(page){
        let data = $data;
        let perPage = $perPage;
        let header = $header;
        
        let start = (page-1) * perPage;
        let end = page * perPage;
        let htmlStr = '';
        for (; start < end; start++) {
            htmlStr += '<tr>';
            for(var index in header){
                if(!data[start]){
                    break;
                }
                if(!data[start][index]){
                    data[start][index] = "undefined '" + index + "'";
                }
                htmlStr += '<td>' + data[start][index] + '<\/td>';
            }
            htmlStr += '<\/tr>';
        }
        document.querySelector('.table-list-{$key}').innerHTML = htmlStr;
        document.querySelector('#table-tbody-position-{$key}').click();
    }
</script>
HTML;
    }


    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function addAttr($name, $value)
    {
        $this->$name = $value;
        return $this;
    }

}