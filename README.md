Laravel-admin Import Components
=============================================
author ：[wenruns](https://github.com/wenruns/laravel-admin-import)

date ：2019年07月15号

____________________

1、功能介绍
----------------------------------------------

    为了简化laravel-admin导入excel文件的功能开发流程，故而出现了该组件。
    组件主要分为三部分：
    1、excel服务应用层：负责导入界面的显示以及配置设置；
    2、excel服务逻辑层：负责处理导入文件处理以及数据库保存；
    3、excel服务响应层：负责回应客户端导入结果。
    
2、安装环境
----------------------------------------------
    laravel版本号：5.5.44
    laravel-admin版本号：1.6.10
    php版本号：7+
    maatwebsite/excel版本号：~2.1.0
    注意：需要开启session

3、安装
----------------------------------------------
```angular2
composer require wenruns/laravel-admin-import
```
4、使用教程
----------------------------------------------
- 控制器（ExampleController.php）
```
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/1
 * Time: 14:27
 */

namespace Wenruns\Excel\example;

use App\Admin\Services\Excel\commission\ExampleService;
use App\Http\Controllers\Controller;
use Wenruns\Excel\import\ExcelServiceApp;
use Encore\Admin\Grid;
use Encore\Admin\Form;


class ExampleController extends Controller
{


    public function import(ExcelServiceApp $excelServiceApp)
    {
        // 导入数据格式化服务
        $importService = new ExampleService(new ExampleModel());
        // 注册服务
        $excelServiceApp->setExcelService($importService)
            // 设置导入列表标题
            ->header('title')
            // 设置导入列表描述
            ->description('This is a test.')
            // 设置导入列表宽度，默认为8
//            ->setListWidth(8)
            // 设置异常判断条件，用于导出时获取异常数据，格式 model方法 =》 参数
            ->setAbnormalConditions([
                'whereNull' => ['filed1']
            ])
            // 设置错误列表显示数据项
            ->setErrHeader([
                'field1' => '标题1',
                'field2' => '标题2',
                'field3' => '标题3',
                'field4' => '标题4',
            ])
            // 导入数据列表展示
            ->gridFun(function (Grid $grid) {

            })
            // 导入form表单，在文件选择框上方显示
            ->formFunUp(function (Form $form) {

            })
            // 导入form表单，在文件选择框下方显示
            ->formFunDown(function (Form $form) {

            });
        return $excelServiceApp->render();
    }
}
```

- Import服务(ExampleService.php)
```
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/7
 * Time: 15:05
 */

namespace App\Admin\Services\Excel\commission;


use Wenruns\Excel\import\ExcelService;
use Wenruns\Excel\import\Response;

class ExampleService extends ExcelService
{

    public function header()
    {
        // TODO: Implement header() method.
        return [
            'field1' => '标题1',
            'field2' => '标题2',
            'field3' => '标题3',
            'field4' => '标题4',
        ];
    }

    /**
     * 格式化导入的数据
     * @param $data 导入数据
     * @return array|bool
     * @throws \Exception
     */
    public function format($data)
    {
        // todo:: 格式化导入数据
        return [
            'data' => [], // 正常数据
            'errData' => [], // 异常数据（错误的数据）
            'existData' => [], // 重复的数据
        ];
    }


    /**
     * 导入成功回调
     * @param Response $response
     */
    public function successCallback(Response $response)
    {

    }

    /**
     * 导入失败回调
     * @param Response $response
     */
    public function failCallback(Response $response)
    {
    }
}
```
