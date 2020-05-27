<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/10/15
 * Time: 15:42
 */

namespace App\Admin\Services\Excel;


use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ExcelServiceApp extends Controller
{

    use HasResourceActions;

    protected $importService = null; // 导入数据处理类实例
    protected $model = null; // 模型

    protected $_formFunUp = null; // from表单，在文件选择框前面
    protected $_formFunDown = null; // form表单，在文件选择框后面

    protected $gridFun = null; // 列表回调处理

    protected $abnormalCondition = []; // 异常数据的条件

    protected $header = ''; //  header标题

    protected $headerUrl = '#';  // header对应的路由

    protected $description = ''; // 描述

    protected $actionUrl = ''; // 表单提交url

    protected $errHeader = []; // 错误数据显示头信息

    protected $batchDelete = false; // 是否显示批量删除

    protected $errorCallback = null; // 错误数据处理

    protected $_functions = []; // excel服务提供者的方法集

    protected $_divisionError = false; // 是否明确分割导入失败的数据是第几次导入，默认不分割

    protected $_divisionSymbol = ''; // 分割符号

    protected $_listWidth = 8; // 左边列表的宽度， 右边表单宽度 = 12 - 左边列表宽度

    protected $_errorDataSessionIndex = '__fail_data_wen__fail_data_wen';

    protected $_importIdsSessionIndex = '__wen_import_ids';

    protected $_responseSessionIndex = 'wen_response_session';


    protected $exportFormat = null;

    protected $_disableAbnormal = false;

    protected $_disableDeleteAbnormal = false;

    protected $_disableError = false;

    protected $_disableDeleteError = false;

    protected $_limit = 1000;


    function __construct(Request $request = null)
    {
    }

    public static $_requireExportWen = true;


    public function setPrefix($index)
    {
        $this->_responseSessionIndex = $index . '_' . $this->_responseSessionIndex;
        $this->_errorDataSessionIndex = $index . '_' . $this->_errorDataSessionIndex;
        $this->_importIdsSessionIndex = $index . '_' . $this->_importIdsSessionIndex;
        return $this;
    }


    /**
     * 隐藏导出异常数据的按钮
     * @param bool $disable
     * @return $this
     */
    public function disableAbnormalButton($disable = true)
    {
        $this->_disableAbnormal = $disable;
        return $this;
    }

    /**
     * 隐藏删除异常数据的按钮
     * @param bool $disable
     * @return $this
     */
    public function disableAbnormalDeleteButton($disable = true)
    {
        $this->_disableDeleteAbnormal = $disable;
        return $this;
    }

    /**
     * 隐藏导出错误数据的按钮
     * @param bool $disable
     * @return $this
     */
    public function disableErrorButton($disable = true)
    {
        $this->_disableError = $disable;
        return $this;
    }

    /**
     * 隐藏删除错误数据的按钮
     * @param bool $disable
     * @return $this
     */
    public function disableDeleteErrorButton($disable = true)
    {
        $this->_disableDeleteError = $disable;
        return $this;
    }

    /**
     * 设置导入失败列表展示的数据
     * @param $headers
     * @return $this
     * @throws \Exception
     */
    public function setErrHeader($headers)
    {
        if (!is_array($headers)) {
            throw new \Exception('setErrHeader需要传入一个数组作为参数，当前参数类型为' . gettype($headers));
        }
        $this->errHeader = $headers;
        return $this;
    }

    /**
     * 设置导出格式化方法
     * @param $func
     * @return $this
     */
    public function setExportFormat($func)
    {
        $this->exportFormat = $func;
        return $this;
    }


    /**
     * 设置导入文件提交路由
     * @param $url
     * @return $this
     */
    public function setAction(string $url)
    {
        $this->actionUrl = $url;
        return $this;
    }

    /**
     * 是否分割错误数据
     * @param string $symbol
     * @return $this
     */
    public function divisionError($symbol = '')
    {
        $this->_divisionError = true;
        $this->_divisionSymbol = $symbol;
        return $this;
    }

    /**
     * 设置左边列表的宽度，右边表单的宽度 = 12 - $w
     * @param int $w
     * @return $this
     */
    public function setListWidth(int $w)
    {
        $this->_listWidth = $w;
        return $this;
    }

    /**
     * 设置Content的header文本
     * @param string $text
     * @param string $url
     * @return $this
     */
    public function header(string $text, $url = '#')
    {
        $this->header = $text;
        $this->headerUrl = $url;
        return $this;
    }

    /**
     * 设置Content的description的文本
     * @param string $text
     * @return $this
     */
    public function description(string $text)
    {
        $this->description = $text;
        return $this;
    }

    public function enableBatchDelete($enable = true)
    {
        $this->batchDelete = $enable;
        return $this;
    }

    /**
     * 设置模型
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * 设置excel服务提供者
     * @param ExcelService $excelService
     * @return $this
     */
    public function setExcelService(ExcelService $excelService)
    {
        $this->importService = $excelService;
        return $this;
    }

    /**
     * form扩展，在表单文件上传按钮（上）
     * @param \Closure $func
     * @return $this
     */
    public function formFunUp($func)
    {
        $this->_formFunUp = $func;
        return $this;
    }

    /**
     * form扩展，在表单文件上传按钮（下）
     * @param \Closure $func
     * @return $this
     */
    public function formFunDown($func)
    {
        $this->_formFunDown = $func;
        return $this;
    }

    /**
     * 设置导入列表grid
     * @param \Closure $func
     * @return $this
     */
    public function gridFun($func)
    {
        $this->gridFun = $func;
        return $this;
    }


    /**
     * 设置异常数据的查询条件
     * @param $where
     * @return $this
     * @throws \Exception
     */
    public function setAbnormalConditions(array $where)
    {
        if (!is_array($where)) {
            throw new \Exception('setAbnormalConditions需传入数组作为参数！当前参数类型为' . gettype($where) . '。');
        }
        $this->abnormalCondition = $where;
        return $this;
    }

    /**
     * 设置错误或异常数据处理回调方法
     * @param \Closure $closure
     * @return $this
     */
    public function formatError($closure)
    {
        $this->errorCallback = $closure;
        return $this;
    }

    /**
     * 展示导入失败的数据
     * @param $res
     * @return Box
     */
    public function showErrData($res)
    {
        $abnormal_data = [];
        $this->errTableHeader();
        if ((\request('op') == 'import'
                || !$this->importService->checkCommit())
            && (!empty($res['errorData'])
                || !empty($res['existData']))) {
            $abnormal_data[] = array_merge($res['errorData'], $res['existData']);
        }
        if ($error_data = session($this->_errorDataSessionIndex)) {
            $abnormal_data = array_merge($error_data, $abnormal_data);
        }
        session([$this->_errorDataSessionIndex => $abnormal_data]);
        $data = [];
        // 由于页面空间有限，只显示一下三个主要字段， headers顺序必须和展示字段顺序相同
        $headers = [];
        $index = 0;
        $total = 0;
        foreach ($abnormal_data as $key => $value) {
            if (!empty($value)) {
                $total += count($value);
                foreach ($value as $ky => $item) {
                    $item = $this->importService->failData($item);
                    foreach ($this->errHeader as $field => $title) {
                        if (!isset($item[$field])) {
                            continue;
                        }
                        if ($index == 0) {
                            $headers[] = $title;
                        }
                        $data[$index][$field] = $item[$field];
                    }
                    $index++;
                }
                if ($this->_divisionError) {
                    foreach ($this->errHeader as $field => $title) {
                        $data[$index][$field] = empty($this->_divisionSymbol) ? '--(' . count($value) . ')--' : $this->_divisionSymbol . '(' . count($value) . ')' . $this->_divisionSymbol;
                    }
                    $index++;
                }
            }
        }
        if (empty($headers)) {
            $data = [];
        }
        // 表格展示失败的数据
        $table = new Table($headers, $data);
        $box = new Box('导入失败的数据（共' . $total . '条记录）', $table->render());

        $box->collapsable();
        // 保存导入失败的数据到session中，用于后面导出功能
        return $box;
    }

    /**
     * 显示导入页面
     * @return Content
     * @throws \Exception
     */
    public function render()
    {
        $this->operate();
        if (empty($this->model) && empty($this->importService)) {
            throw new \Exception('请设置模型和Excel服务提供者。');
        } else if (empty($this->model)) {
            throw new \Exception('请设置模型。');
        } else if (empty($this->importService)) {
            throw new \Exception('请设置Excel服务提供者。');
        }
        $this->callFunction();
        $content = new Content();
        if ($this->header) {
            $breadcrumb[] = ['text' => $this->header, 'url' => $this->headerUrl];
        }
        $breadcrumb[] = ['text' => '报表导入'];
        if (\request('op') == 'import' && session($this->_responseSessionIndex)) {
            $res = $this->importService->saveAllByCustomer($this->getResponseFromSession());
        } else {
            $res = $this->importService->saveAll();
            $this->checkCustomerChoice();
        }
        return $content->header($this->header ? $this->header : '报表导入')
            ->description($this->description ? $this->description : '导入数据列表')
            ->breadcrumb(...$breadcrumb)
            ->row(function (Row $row) use ($res) {
                $row->column($this->_listWidth, $this->showData($res));
                $row->column((12 - $this->_listWidth), function (Column $column) use ($res) {
                    $column->row($this->showForm($res));
                    $column->row($this->showErrData($res));
                });
            });
    }


    /**
     * 从session中获取等待导入的数据
     * @return mixed
     */
    protected function getResponseFromSession()
    {
        $response = unserialize(session($this->_responseSessionIndex));
        session([$this->_responseSessionIndex => '']);
        $types = explode('.', \request('types'));
        foreach ($types as $type) {
            switch ($type) {
                case 'abnormal':
                    $response->setSuccessData(array_merge($response->getSuccessData(), $response->getErrorData()));
                    $response->setErrorData([]);
                    break;
                case 'repeat':
                    $response->setSuccessData(array_merge($response->getSuccessData(), $response->getExistData()));
                    $response->setExistData([]);
                    break;
                default:
            }
        }
        return $response;
    }

    /**
     * 检测是否做出反应
     */
    protected function checkCustomerChoice()
    {
        if ($this->importService->checkCommit()) {
            session([$this->_responseSessionIndex => serialize($this->importService->_response)]);
            $content = $this->showCustomerHtml();
            $url = $this->makeUrl('op=import');
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
        $errData = $this->importService->_response->getErrorData();
        $existData = $this->importService->_response->getExistData();
        $data = $this->importService->_response->getSuccessData();
        $errCount = count($errData);
        $existCount = count($existData);
        $dataCount = count($data);
        $totalCount = $errCount + $existCount + $dataCount;

        $errPercent = ($errCount / ($existCount + $errCount + $dataCount) * 10000) / 100;
        $exitPercent = ($existCount / ($existCount + $errCount + $dataCount) * 10000) / 100;


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
                {$this->abnormalButton()}
                {$this->repeatButton()}
            </tr>
            <tr>
                <td colspan="8">
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
                });
                e.target.style.background = "#FF8000";
                e.target.style.color = "#ffffff";
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
        return $this->importService->insertWithExistData();
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
    <td style="border:1px solid #000000;padding: 5px;">重复</td>
    <td style="border:1px solid #000000;padding: 5px;">{$existCount}条</td>
    <td style="border:1px solid #000000;padding: 5px;">重复率</td>
    <td style="border:1px solid #000000;padding: 5px;">{$exitPercent}%</td>
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
        $style = 'background: #FFD1A4;color: #000000;';
        if ($this->checkAbnormal()) {
            $style = 'background: #FF8000;color: #FFFFFF;';
        }
        return <<<HTML
<td colspan="4" style="border:1px solid #000000;padding: 5px; cursor: pointer;{$style}" class="wen-import-button" data-flag="repeat">重复数据</td>
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
        $display = 'display: none;';
        if ($this->checkAbnormal()) {
            $display = '';
        }
        $tableExist = $this->makeTable($existData);
        return <<<HTML
<div class="table-list table-list-repeat" style="{$display}">
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
        return $this->importService->insertWithErrorData();
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
    <td style="border:1px solid #000000;padding: 5px;">异常</td>
    <td style="border:1px solid #000000;padding: 5px;">{$errCount}条</td>
    <td style="border:1px solid #000000;padding: 5px;">异常率</td>
    <td style="border:1px solid #000000;padding: 5px;">{$errPercent}%</td>
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
        return <<<HTML
<td colspan="4" style="border:1px solid #000000;padding: 5px; background: #FF8000;color: #FFFFFF;cursor: pointer;" class="wen-import-button" data-flag="abnormal">异常数据</td>
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
<div class="table-list table-list-abnormal">
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
        $header = $header = $this->errHeader;
        if (empty($header)) {
            $header = $this->importService->setHeader();
        };
        $theadHtml = '';
        foreach ($header as $key => $title) {
            $theadHtml .= '<th>' . $title . '</th>';
        }
        $tbodyHtml = '';
        foreach ($data as $key => $item) {
            $item = $this->importService->failData($item);
            if ($key < $perPage) {
                $tbodyHtml .= '<tr>';
                foreach ($header as $field => $title) {
                    if (!isset($item[$field])) {
                        $item[$field] = "undefined '$field'";
                    }
                    $tbodyHtml .= '<td>' . $item[$field] . '</td>';
                }
                $tbodyHtml .= '</tr>';
            }
            $data[$key] = $item;
        }
        $colspan = count($header);

        $total = ceil(count($data) / $perPage);
        $pageHtml = '';
        for ($i = 1; $i <= $total; $i++) {
            if ($i == 1) {
                $pageHtml .= "<li data-page='$i' class='page-choose'>$i</li>";
            } else {
                $pageHtml .= "<li data-page='$i'>$i</li>";
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
        justify-content: flex-end;
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
    let pages_{$key} = document.querySelectorAll('.table-list-content-{$key} .table-list-page li');
    pages_{$key}.forEach(function(item, index) {
        item.addEventListener('click', function(e) {
            pages_{$key}.forEach(function(obj, dex) {
                obj.classList.remove('page-choose')
            });
            e.target.classList.add('page-choose');
            makePageContent{$key}(e.target.dataset.page)
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


    protected function showImportResult($result)
    {
        if (empty($result['data']) && empty($result['err_data']) && empty($result['exist_data'])) {
            return;
        }
    }

    /**
     * 调用excel服务提供者的方法
     */
    protected function callFunction()
    {
        foreach ($this->_functions as $key => $item) {
            $method = $item['method'];
            $params = $item['params'];
            $this->importService->$method(...$params);
        }
    }


    /**
     * 需要执行的操作（导出 | 删除）
     */
    protected function operate()
    {
        $op = \request('op');
        switch ($op) {
            case 'export':
                $this->exportOperate();
                break;
            case 'delete':
                $this->deleteOperate();
                break;
            default:
        }
    }

    /**
     * 导出操作
     */
    protected function exportOperate()
    {
        $type = \request('type');
        switch ($type) {
            case 'abnormal':
                $this->export();
                break;
            case 'fail':
                $this->exportFail();
                break;
            default:
        }
    }

    /**
     * 删除操作
     */
    protected function deleteOperate()
    {
        $type = \request('type');
        switch ($type) {
            case 'abnormal':
                $this->deleteAbnormal();
                break;
            case 'fail':
                $this->clearFailData();
                break;
            case 'batch':
                $this->deleteAll();
                break;
            default:
        }
    }


    /**
     * 初始化导入错误列表信心
     */
    protected function errTableHeader()
    {
        if (empty($this->errHeader)) {
            $i = 0;
            foreach ($this->importService->setHeader() as $field => $title) {
                $this->errHeader[$field] = $title;
                $i++;
                if ($i > 2) {
                    break;
                }
            }
        }
    }

    /**
     * 导入表单，上传xlxs文件
     * @param $res
     * @return Form
     */
    public function showForm($res)
    {
        $form = new Form($this->model);
        $form->tools(function ($tools) {
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();
        });
        $form->footer(function ($footer) {                // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
        $form->setTitle('导入交易记录');
        $form->setAction($this->makeUrl(''));

        if (is_callable($this->_formFunUp)) {
            call_user_func($this->_formFunUp, $form);
        }
        $form->file("import", '文件')->required();
        if (is_callable($this->_formFunDown)) {
            call_user_func($this->_formFunDown, $form);
        }
        return $form;
    }

    /**
     * 生成导入或导出或删除的url
     * @param $params
     * @return string
     */
    protected function makeUrl($params)
    {
        if (empty($this->actionUrl)) {
            $url = \request()->getUri();
            if (strpos($url, 'op=') !== false) {
                $url = mb_substr($url, 0, strpos($url, 'op='));
            }
        } else {
            $url = $this->actionUrl;
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

    public function enableInsertWithErrorData($enable = true)
    {
        $this->_functions[] = [
            'method' => 'enableInsertWithErrorData',
            'params' => [$enable]
        ];
        return $this;
    }

    public function enableInsertWithExistData($enable = true)
    {
        $this->_functions[] = [
            'method' => 'enableInsertWithExistData',
            'params' => [$enable]
        ];
        return $this;
    }

    public function enableInsertWithCustomerChoice($enable = true)
    {
        $this->_functions[] = [
            'method' => 'enableInsertWithCustomerChoice',
            'params' => [$enable]
        ];
        return $this;
    }


    /**
     * 导入数据展示
     * @param $res
     * @return Grid
     */
    public function showData($res)
    {
        $grid = new Grid($this->model);
        $ids = isset($res['result']['res']) ? $res['result']['res'] : [];
        $ids = array_merge(session($this->_importIdsSessionIndex, []), $ids);
        session([$this->_importIdsSessionIndex => $ids]);
        $grid->model()->whereIn($this->model->getKeyName(), $ids);
        if (is_callable($this->gridFun)) {
            call_user_func($this->gridFun, $grid, $res);
        } else {
            foreach ($this->importService->setHeader() as $field => $title) {
                $grid->$field($title);
            }
        }
        // 添加两个自定义按钮
        $grid->tools(function ($tools) {
            $excelBody = json_encode(array_keys($this->importService->setHeader()));
            $excelHead = json_encode(array_values($this->importService->setHeader()));
            $elementId = [
                'wen-export-abnormal',
                'wen-export-delete',
            ];
            $executeTag = 'error';
            if ($this->_disableError) {
                unset($elementId[array_search('wen-export-delete', $elementId)]);
                $executeTag = 'abnormal';
            }
            if ($this->_disableAbnormal) {
                unset($elementId[array_search('wen-export-abnormal', $elementId)]);
            }
            $elementId = json_encode($elementId);

            //  http://wenruns.gitee.io/js/export_wen.js
            $tags = <<<EOT
                <script src="/js/export_wen.js"></script>
EOT;
            if (\request('_pajx') || (\request('op') && \request('op') != 'import') || !empty(\request()->file())) {
                $tags = '';
            }
            $format = $this->importService->exportFormat();
            $tags .= <<<EOT
                <script >
                    if(typeof exportDriver == 'undefined'){
                        let exportDriver = new export_wen({
                            elementID: $elementId,
                            excelHead: $excelHead,
                            excelBody: $excelBody,
                            dataFormat:function(data, field, elementID) {
                                let format = $format;
                                if (format){
                                    return format(data, field);
                                }
                                return data[field];
                            } 
                        });
                    }
                    
                </script>
EOT;

            $this->_disableDeleteAbnormal || empty($this->abnormalCondition) || $tools->append((new CreateBtn([
                'url' => $this->makeUrl('op=delete&type=abnormal'),
                'text' => '删除异常数据',
                '_prefix' => 'deleteAbnormal',
                'style' => [
                    'background' => '#FFA500',
                    'color' => '#fff',
                ],
                'hover_style' => [
                    'background' => '#FF8C00',
                    'color' => '#fff',
                ],
            ]))->render());
            $this->_disableAbnormal || empty($this->abnormalCondition) || $tools->append((new CreateBtn([
                'text' => '导出异常数据',
                '_target' => '_blank',
                'disableClickEvent' => true,
                'attributes' => [
                    'id' => 'wen-export-abnormal',
                    'data-configure' => json_encode([
                        'fileName' => '导入异常数据.xls',
                        'url' => $this->makeUrl('op=export&type=abnormal'),
                    ]),
                ],
                'style' => [
                    'background' => '#F4A460',
                    'color' => '#fff'
                ],
                'hover_style' => [
                    'background' => '#D2691E',
                    'color' => '#fff'
                ],
                'tags' => $executeTag == 'abnormal' ? $tags : '',
            ]))->render());

            $this->_disableDeleteError || $tools->append((new CreateBtn([
                'url' => $this->makeUrl('op=delete&type=fail'),
                'text' => '清空失败数据',
                '_prefix' => 'clearFailData',
                'style' => [
                    'background' => '#FF4500',
                    'color' => '#fff',
                ],
                'hover_style' => [
                    'background' => '#FF0000	',
                    'color' => '#fff',
                ],
            ]))->render());

            $this->_disableError || $tools->append((new CreateBtn([
                'text' => '导出失败的数据',
                '_prefix' => 'export_fail',
                '_target' => '_blank',
                'disableClickEvent' => true,
                'attributes' => [
                    'id' => 'wen-export-delete',
                    'data-configure' => json_encode([
                        'fileName' => '导入失败数据.xls',
                        'url' => $this->makeUrl('op=export&type=fail')
                    ])
                ],
                'tags' => $executeTag == 'error' ? $tags : '',
                'style' => [
                    'background' => '#FA8072',
                    'color' => '#fff',
                ],
                'hover_style' => [
                    'background' => '#FF6347',
                    'color' => '#fff',
                ],
            ]))->render());

            #批量删除
            $tools->batch(function ($batch) {
                $batch->disableDelete();
                if ($this->batchDelete) {
                    $batch->add('删除', new BatchTools([
                        'action' => BatchTools::BATCH_DELETE,
                        'url' => $this->makeUrl('op=delete&type=batch'),
                        'method' => 'delete'
                    ]));
                }
            });
        });

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableActions();
        if (!$this->batchDelete) {
            $grid->disableRowSelector();
        }
        return $grid;
    }


    /**
     * 删除选中的数据
     * @return mixed
     */
    public function deleteAll()
    {
        $ids = \request('ids');
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        if (!empty($ids)) {
            return $this->model->whereIn('id', $ids)->delete();
        }
    }


    /**
     * 导出异常数据
     */
    public function export()
    {
        $page = \request('page');
        $limit = 1000;
        $ids = session($this->_importIdsSessionIndex, []);
        $model = $this->model->whereIn($this->model->getKeyName(), $ids);
        foreach ($this->abnormalCondition as $key => $value) {
            if (is_array($value)) {
                $model = $model->$key(...$value);
            } else {
                $model = $model->$key($value);
            }
        }
//        dd($this->abnormalCondition, $model);
        echo json_encode($model->limit($limit)->offset($page * $limit)->get()->toArray());
        exit(0);
    }


    /**
     * 删除异常数据
     */
    public function deleteAbnormal()
    {
        $model = $this->model;
        foreach ($this->abnormalCondition as $key => $value) {
            if (is_array($value)) {
                $model = $model->$key(...$value);
            } else {
                $model = $model->$key($value);
            }
        }
        $model->delete();
    }


    /**
     * 清空失败数据
     */
    public function clearFailData()
    {
        session([$this->_errorDataSessionIndex => '']);
    }


    /**
     * 导出失败数据
     */
    public function exportFail()
    {
        if (\request('page') > 0) {
            echo json_encode([]);
            exit(0);
        }
        $data = [];
        foreach (session($this->_errorDataSessionIndex) as $key => $item) {
            $data = array_merge($data, $item);
        }
        echo json_encode($data);
        exit(0);
    }


    /**
     * 调用不存在的方法，存起来，可能是格式
     * @param string $method
     * @param array $parameters
     * @return $this|mixed
     */
    public function __call($method, $parameters)
    {
        $this->_functions[] = [
            'method' => $method,
            'params' => $parameters
        ];
        return $this;
    }
}