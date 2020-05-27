<?php

namespace App\Admin\Services\Excel\Sales;

use App\Admin\Services\Excel\ExcelService;
use App\Admin\Services\Excel\Response;
use App\Admin\Services\Excel\ShowLayer;
use App\Admin\Services\Excel\UploadFile;
use App\Models\ImportSalesData;
use App\Models\ImportSalesDataLog;
use Encore\Admin\Facades\Admin;


class ImportService extends ExcelService
{

    /**
     *  格式化xlsx数据
     * @param $data
     * @return array
     * @throws \Exception
     */
    public function format($data)
    {
        $instance = new FormatService($data);
        return $instance->getResult();
    }

    /**
     * @param $data
     * @return array
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

    public function setHeader(): array
    {
        return FormatService::setAttributes();
    }


    public function failData($data): array
    {
        // TODO: Change the autogenerated stub
        $data['trade_date'] = !empty($data['trade_date']) ? date('Y年m月', $data['trade_date']) : '';
        $data['import_date'] = !empty($data['import_date']) ? date('Y-m-d', $data['import_date']) : '';
        return $data;
    }

    /**
     * @param Response $response
     */
    public function successCallback(Response $response)
    {
        $result = $response->getResult();
        $files = $this->getExcelFiles();
        $fileNames = array_column($files, 'fileName');
        if ($result['code'] == 200 && !empty($result['res'])) {
            $logData = [
                'staff_code' => Admin::user()->staff_code,
                'staff_name' => Admin::user()->staff_name,
                'file_name' => implode(',', $fileNames),
                'import_ids' => json_encode($result['res']),
                'type' => ImportSalesDataLog::IMPORT_TYPE_SALES,
                'created_at' => time(),
                'updated_at' => time(),
            ];
            ImportSalesDataLog::insert($logData);
        }

        $success = count($response->getSuccessData());
        $fail = count($response->getErrorData());
        $repeat = count($response->getExistData());
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

    public function failCallback(Response $response)
    {
        $result = $response->getResult();
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
     * 文件处理
     * @param $file
     * @param $info
     */
    public function fileCallback($file, $info)
    {
//        parent::fileCallback($file, $info); // TODO: Change the autogenerated stub
//        dd($file);
    }


    public function beforeInsert(Response $response)
    {
//        parent::beforeInsert($response); // TODO: Change the autogenerated stub
//        dd($response);
    }
}