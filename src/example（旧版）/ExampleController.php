<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/1
 * Time: 14:27
 */

namespace Wenruns\Example;



use Wenruns\Import\ExcelServiceApp;

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