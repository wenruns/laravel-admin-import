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
- 控制器（Example.php）
```
namespace  App\Admin\Controllers;

use App\Http\Controllers\Controller;

class Example extends Controller 
{
    /**
     * 导入
     * @param ExcelServiceApp $excelService
     * @return Content
     * @throws \Exception
     */
    public function import(ExcelServiceApp $excelService)
    {
        $this->checkResult();
        $model = new ImportSalesData();
        // 实例化逻辑层服务
        $importService = new ImportService($model);
        // 设置（注册）逻辑层服务
        $excelService->setExcelService($importService);
        // 应用层服务
        $excelService->header('交易记录');
        // 设置模型
        $excelService->setModel($model);
        // 设置导入错误数据的分割
        $excelService->divisionError();
        // 设置导入列表的宽度
        $excelService->setListWidth(8);
        // 设置允许导入错误数据
//        $excelService->enableInsertWithErrorData();
        // 设置允许用户选择性导入
        $excelService->enableInsertWithCustomerChoice();
        // 隐藏异常数据删除按钮
        $excelService->disableAbnormalDeleteButton();
        // 设置异常数据的判断条件
        $excelService->setAbnormalConditions([
            'where' => ['system', 'unlink']
        ]);
        $excelService->setErrHeader([
            'period_number' => '银行贷款编号',
            'custom_name' => '客户名称',
            'trade_amount' => '支用累计',
            'trade_date' => '支用月份',
//            'import_date' => '导入时间',
            'err_msg' => '信息',
        ]);
        $excelService->gridFun(array($this, 'importGrid'));
        $excelService->formFunDown(array($this, 'importForm'));
        return $excelService->render();
    }

}
```
