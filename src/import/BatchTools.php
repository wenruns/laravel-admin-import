<?php
namespace App\Admin\Services\Excel;

use Encore\Admin\Grid\Tools\BatchAction;

/**
 * (批量工具)
 * 参考链接：http://laravel-admin.org/docs/zh/model-grid-custom-tools
 * Class BatchTools
 * @package App\Admin\Extensions\Tools
 *
 * @author wen
 *
 * @property int $action 操作标志位（目前自定义一个1：批量删除），可通过参数options传入action修改
 * @property string $url 按钮请求路径
 * @property string $suc_tips 请求或操作成功时弹出的提示内容
 * @property string $err_tips 请求或者操作失败时弹出的提示内容
 * @property string $request_method 请求的方式，例如POST、GET、DELETE、PUT
 */
class BatchTools extends BatchAction
{
    // 动作标志
    protected $action;
    // 请求路径
    protected $url = null;
    // 请求成功提示语
    protected $suc_tips = '';
    // 请求错误提示语
    protected $err_tips = '';
    // 请求方式 【get|post|delete 等等】
    protected $request_method = '';



    const BATCH_DELETE = 1;  // 批量删除


    /**
     * BatchTools constructor.
     * @param $options
     * 1、action 操作标志位
     * 2、url 请求路径
     * 3、success_text 请求成功提示语
     * 4、err_text 请求失败提示语
     * 5、method 请求方式
     */
    public function __construct($options)
    {
        $this->action = isset($options['action']) ? $options['action'] : 1;
        $this->url = isset($options['url']) && $options['url'] ? $options['url'] : $this->resource.'/delete';
        $this->suc_tips = isset($options['success_text']) ? $options['success_text'] : '操作成功！';
        $this->err_tips = isset($options['err_text']) ? $options['err_text'] : '操作失败！';
        $this->request_method = isset($options['method']) && $options['method'] ? $options['method'] : 'post';
    }

    /**
     * @return mixed|string
     */
    public function script()
    {
        // TODO: Implement script() method.
        return <<<EOT
$('{$this->getElementClass()}').on('click', function() {
    $.ajax({
        method: '{$this->request_method}',
        url: '{$this->url}',
        data: {
            _token:LA.token,
            ids: selectedRows(),
            action: {$this->action},
        },
        success: function (res) {
            $.pjax.reload('#pjax-container');
            console.log(res);
            toastr.success('{$this->suc_tips}');
        },
        fail: function(err) {
            toastr.success('{$this->err_tips}');
        }
    });
});
EOT;
    }
}