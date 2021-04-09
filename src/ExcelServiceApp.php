<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/10/15
 * Time: 15:42
 */

namespace App\Admin\Services\Excel;


use App\Admin\Services\Excel\Layout\BatchTools;
use App\Admin\Services\Excel\Layout\Button;
use App\Admin\Services\Excel\Model\ErrorDataModel;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Illuminate\Database\Eloquent\Model;

class ExcelServiceApp extends Controller
{

    use HasResourceActions;

    /**
     * 导入数据处理类实例
     * @var ExcelService
     */
    protected $importService = null;

    /**
     * 模型
     * @var null
     */
    protected $model = null;

    /**
     * from表单，在文件选择框前面
     * @var null
     */
    protected $_formFunUp = null;

    /**
     * form表单，在文件选择框后面
     * @var null
     */
    protected $_formFunDown = null;

    /**
     * 列表回调处理
     * @var null
     */
    protected $gridFun = null;

    /**
     * 异常数据的条件
     * @var array
     */
    protected $abnormalCondition = [];

    /**
     * @var string |Grid
     */
    protected $gridClass = Grid::class;

    /**
     *  header标题
     * @var string
     */
    protected $header = '';

    /**
     * header对应的路由
     * @var string
     */
    protected $headerUrl = '#';

    /**
     * 描述
     * @var string
     */
    protected $description = '';

    /**
     * 是否显示批量删除
     * @var bool
     */
    protected $batchDelete = false;

    /**
     * 错误数据处理
     * @var null
     */
    protected $errorCallback = null;

    /**
     * excel服务提供者的方法集
     * @var array
     */
    protected $_functions = []; //

    /**
     * 是否明确分割导入失败的数据是第几次导入，默认不分割
     * @var bool
     */
    protected $_divisionError = false;

    /**
     * 分割符号
     * @var string
     */
    protected $_divisionSymbol = '';

    /**
     * @var int
     */
    protected $_listWidth = 8; // 左边列表的宽度， 右边表单宽度 = 12 - 左边列表宽度

    /**
     * 索引前缀
     * @var string
     */
    protected $_sessionPrefix = '';

    /**
     * @var string
     */
    protected $_errorDataSessionIndex = '__fail_data_wen__fail_data_wen';

    /**
     *
     * @var string
     */
    protected $_importIdsSessionIndex = '__wen_import_ids';

    /**
     * form表单导入文件帮助说明
     * @var array
     */
    protected $_importInputHelp = [
        'text' => '',
        'icon' => '',
    ];

//    protected $_responseSessionIndex = 'wen_response_session';

    /**
     * 导出数据格式化
     * @var null
     */
    protected $exportFormat = null;

    /**
     * 是否关闭异常数据按钮
     * @var bool
     */
    protected $_disableAbnormal = false;

    /**
     * 是否关闭异常数据删除按钮
     * @var bool
     */
    protected $_disableDeleteAbnormal = false;

    /**
     * 是否关闭错误数据按钮
     * @var bool
     */
    protected $_disableError = false;

    /**
     * 是否关闭错误数据删除按钮
     * @var bool
     */
    protected $_disableDeleteError = false;

    /**
     *
     * @var int
     */
    protected $_limit = 1000;


    /**
     *
     * @var \Closure|null
     */
    protected $_initCallback = null;


    /**
     * ExcelServiceApp constructor.
     * @param ExcelService $importService excel服务提供者
     */
    function __construct(ExcelService $importService)
    {
        $this->importService = $importService;
    }

    public function beforeLoad(\Closure $closure)
    {
        $this->_initCallback = $closure;
        return $this;
    }

    /**
     * 获取错误数据保存session的索引
     * @return string
     */
    public function getErrorDataCacheIndex()
    {
        return $this->_sessionPrefix . $this->getModel()->getTable() . $this->_errorDataSessionIndex;
    }

    /**
     * @return string
     */
    public function getImportIdsCacheIndex()
    {
        return $this->_sessionPrefix . $this->getModel()->getTable() . $this->_importIdsSessionIndex;
    }

    public function clearCacheData()
    {
        session([$this->getErrorDataCacheIndex() => []]);
        session([$this->getImportIdsCacheIndex() => []]);
        return $this;
    }

    /**
     * @param $gridClass
     * @return $this
     */
    public function setGridClass($gridClass)
    {
        $this->gridClass = $gridClass;
        return $this;
    }

    /**
     * 设置前缀
     * @param $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->_sessionPrefix = $prefix;
        $this->_functions[] = [
            'method' => 'setSessionPrefix',
            'params' => [$prefix]
        ];
        return $this;
    }

    /**
     * 帮助说明
     * @param $text
     * @param string $icon
     * @return $this
     */
    public function help($text, $icon = '')
    {
        $this->_importInputHelp = [
            'text' => $text,
            'icon' => $icon,
        ];
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
        $this->importService->errHeader($headers);
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
        $this->importService->url($url);
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
     * @return Model|null
     */
    public function getModel()
    {
        if (empty($this->model)) {
            $this->model = $this->importService->model();
        }
        return $this->model;
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

    protected function cacheErrorData($res)
    {
        $abnormal_data = [];
        if ((\request('op') == 'import'
                || !$this->importService->checkCommit())
            && (!empty($res['errorData'])
                || !empty($res['existData']))) {
            $abnormal_data[] = array_merge($res['errorData'], $res['existData']);
        }
        if ($error_data = session($this->getErrorDataCacheIndex())) {
            $abnormal_data = array_merge($error_data, $abnormal_data);
        }
        session([$this->getErrorDataCacheIndex() => $abnormal_data]);
        return $this;
    }

    protected $_styleCode = <<<CSS
.box{
    border: 0px !important;
}
CSS;


    /**
     * 格式化
     * @param $headers
     * @param $data
     * @param $item
     * @param $index
     */
    protected function formatErrorData(&$data, $item, $index)
    {
        $item = $this->importService->failData($item);
        foreach ($this->importService->getErrHeader() as $field => $title) {
            $data[$index][$field] = $item[$field];
        }
    }


    /**
     * 显示导入页面
     * @return Content
     * @throws \Exception
     */
    public function render()
    {
        if (empty($this->importService)) {
            throw new \Exception('请设置Excel服务提供者。');
        }
        if (is_callable($this->_initCallback)) {
            call_user_func($this->_initCallback, $this);
        }
        $this->operate();

        $this->callFunction();
        $content = new Content();
        if ($this->header) {
            $breadcrumb[] = ['text' => $this->header, 'url' => $this->headerUrl];
        }
        $breadcrumb[] = ['text' => '报表导入'];
        if (\request('op') == 'import' && $response = $this->getResponseFromSession()) {
            $res = $this->importService->saveAllByCustomer($response);
        } else {
            $res = $this->importService->saveAll();
        }
        $this->cacheErrorData($res);

        return $content->header($this->header ? $this->header : '报表导入')
            ->description($this->description ? $this->description : '导入数据列表')
            ->breadcrumb(...$breadcrumb)
            ->row(function (Row $row) use ($res) {
                $row->column($this->_listWidth, $this->showData($res));
                $row->column((12 - $this->_listWidth), $this->showForm($res));
            });
    }


    /**
     * 从session中获取等待导入的数据
     * @return mixed
     */
    protected function getResponseFromSession()
    {
        $response = $this->importService->getResponseFromSession();
        if ($response) {
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
        }
        return $response;
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
        $type = \request('import_button_type');
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
        $type = \request('import_button_type');
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
     * 导入表单，上传xlxs文件
     * @param $res
     * @return Form
     */
    public function showForm($res)
    {
        $form = new Form($this->getModel());
        $form->tools(function (Form\Tools $tools) {
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();
        });
        $form->footer(function (Form\Footer $footer) {                // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
        $form->setTitle('数据导入');
        $form->setAction($this->makeUrl(''));

        if (is_callable($this->_formFunUp)) {
            call_user_func($this->_formFunUp, $form);
        }
        $form->file("import", '文件')
            ->required()
            ->help(
                $this->_importInputHelp['text'],
                $this->_importInputHelp['icon']
            )
            ->style('height', '100%');
        if (is_callable($this->_formFunDown)) {
            call_user_func($this->_formFunDown, $form);
        }
        return $form;
    }

    public function baseUri($uri)
    {
        $this->importService->url($uri);
        return $this;
    }

    /**
     * 生成导入或导出或删除的url
     * @param $params
     * @return string
     */
    protected function makeUrl($params)
    {
        return $this->importService->makeUrl($params);
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

    /**
     * @param bool $enable
     * @return $this
     */
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
        if (request('op') == 'getErrorList') {
            $isErrorData = true;
            $model = new ErrorDataModel();
            $model->sourceData(session($this->getErrorDataCacheIndex(), []));
            $grid = new $this->gridClass($model);
            $ids = session($this->getImportIdsCacheIndex(), []);
        } else {
            $isErrorData = false;
            $grid = new $this->gridClass($this->getModel());
            $data = isset($res['result']['data']) ? $res['result']['data'] : [];
            // 展示今天导入的记录
            $ids = array_merge(session($this->getImportIdsCacheIndex(), []), array_column($data, $this->getModel()->getKeyName()));
            session([$this->getImportIdsCacheIndex() => $ids]);
            $grid->model()->whereIn($this->getModel()->getTable() . '.' . $this->getModel()->getKeyName(), $ids);
        }

        $grid->tools(function (Grid\Tools $tools) use ($isErrorData, $grid, $ids) {
            $normalNumber = $this->getModel()->whereIn($this->getModel()->getTable() . '.' . $this->getModel()->getKeyName(), $ids)->count();
            $abnormalNumber = count(array_merge([], ...session($this->getErrorDataCacheIndex(), [])));
            $tools->append(Button::create([
                [
                    'buttonText' => '导入数据列表',
                    'class'      => 'import-button-normal btn btn-sm btn-' . ($isErrorData ? 'info' : 'default'),
                    'url'        => $this->makeUrl('op=getDataList'),
                    'css'        => [
                        '.import-button-normal:after' => [
                            'color'       => 'red',
                            'content'     => '"(' . $normalNumber . ')"',
                            'font-weight' => 'bold',
                        ]
                    ],
                ], [
                    'buttonText' => '失败数据列表',
                    'class'      => 'import-button-abnormal btn btn-sm btn-' . ($isErrorData ? 'default' : 'info'),
                    'url'        => $this->makeUrl('op=getErrorList'),
                    'css'        => [
                        '.import-button-abnormal:after' => [
                            'color'       => 'red',
                            'content'     => '"(' . $abnormalNumber . ')"',
                            'font-weight' => 'bold',
                        ]
                    ],
                ], [
                    'url'        => $this->makeUrl('op=delete&import_button_type=fail'),
                    'buttonText' => '清空失败数据',
                    'class'      => 'btn btn-sm btn-danger',
                ], [
                    'url'        => $this->makeUrl('op=delete&import_button_type=abnormal'),
                    'buttonText' => '删除异常数据',
                    'class'      => 'btn btn-sm btn-warning',
                    'show'       => !$this->_disableDeleteAbnormal && !empty($this->abnormalCondition)
                ]
            ]));
            #批量删除
            $tools->batch(function ($batch) use ($isErrorData) {
                $batch->disableDelete();
                if (!$isErrorData && $this->batchDelete) {
                    $batch->add('删除', new BatchTools([
                        'action' => BatchTools::BATCH_DELETE,
                        'url'    => $this->makeUrl('op=delete&import_button_type=batch'),
                        'method' => 'delete'
                    ]));
                }
            });
        });
        if (is_callable($this->gridFun)) {
            call_user_func($this->gridFun, $grid, $isErrorData ? null : $ids);
        } else {
            foreach ($this->importService->header() as $field => $title) {
                $grid->$field($title);
            }
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
            return $this->getModel()->whereIn('id', $ids)->delete();
        }
    }


    /**
     * 导出异常数据
     */
    public function export()
    {
        $page = \request('page');
        $limit = 1000;
        $ids = session($this->getImportIdsCacheIndex(), []);
        $model = $this->getModel()->whereIn($this->getModel()->getKeyName(), $ids);
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
        $model = $this->getModel();
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
        session([$this->getErrorDataCacheIndex() => '']);
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
        foreach (session($this->getErrorDataCacheIndex()) as $key => $item) {
            $data = array_merge($data, $item);
        }
        foreach ($data as $key => $item) {
            $data[$key] = $this->importService->failData($item);
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


    public static function instance($serverClass, $modelClass)
    {
        return new self(new $serverClass(new $modelClass));
    }
}