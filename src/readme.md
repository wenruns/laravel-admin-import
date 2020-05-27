|=============================================|
|              author： wenruns               |
|=============================================|
|               2019年07月15号                |
|=============================================|
#excel导入导出服务使用说明
##1、创建一个类继承于ExcelService类，该类须实现三个方法：setImport、makeResponse、setExport
###a）setImport方法和setExport方法
    作用：该方法用于设置导入数据时的一些配置参数和回调方法。
    参数：ExcelPrivider $excel 一个excel导出实例对象
    ExcelPrivider对象拥有以下方法：
        1)setFormat
            作用：设置导入数据格式化回调，导入之前调用该方法设置的回调方法格式化数据；
            参数：function $func 格式化数据的回调方法，该方法将接收一个参数，导入的数据集合$data
        2)setTable
            作用：设置需要导入的数据表名
            参数：string $table
        3)setDb
            作用：设置需要导入的数据库连接信息
            参数：string $db
        4)setFailCallback
            作用：设置导入数据库失败时的回调方法，插入数据失败时调用
            参数：function $func 该方法接收一个参数，导入失败的数据集合$data
        5)setExportFileName
            作用：设置导出的文件名称
            参数：string $name
        6)setExportData
            作用：设置导出的数据集合
            参数：array $data
        7)setExportFormat
            作用：设置导出前格式化数据的回到方法
            参数：function $func 该方法将接收一个数据集合
        8)setExportType
            作用：设置导出的文件类型
            参数：string $type
        9)setHeader
             作用：设置内容的标题栏，即数据集合每一列的名称
             参数：array $head
###b）makeResponse方法
    作用：该方法用于自定义导入时的返回结果集合
    参数：array $res 导入数据库的结果