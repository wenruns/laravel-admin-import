<?php
/**
 * |***************************|
 * |   author |   wenruns      |
 * |***************************|
 * |   date   |   2019-07-15   |
 * |***************************|
 * 1、描述
 *    类名：CreateBtn
 *    功能：创建自定义按钮
 *    1）方法：createBtn
 *       描述：创建自定义按钮
 *       参数：array $options 配置参数
 *    参数可选项：
 *       style：自定义按钮样式
 *       hover_style：自定义鼠标经过样式
 *       visited_style：自定义按钮点击后的样式
 *       attributes：自定义按钮属性
 *       url：点击按钮跳转url
 *       text：自定义按钮文本
 *       script：自定义javascript代码（注意：js代码不需要<script>标签）
 *       _prefix：前缀
 *       _target：页面跳转方法【_blank：新页面打开】
 *       selectRows：true或者false，true表示将选中行的id追加到url后面
 */

namespace App\Admin\Services\Excel;


class CreateBtn
{
    protected $options = [];

    public function __construct($options = [])
    {
        $this->options = $options;
    }

    public function render()
    {
        return $this->createBtn($this->options);
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

    public static function __callStatic($name, $arguments)
    {

    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
    }

    /**
     * @param array $options 用户可在此参数设置按钮样式（style|可以是一个数组，也可以是字符串），鼠标经过按钮样式(hover_style|可以为数组，也可以为字符串)，按钮点击后的样式(visited_style|可以为数组，也可以为字符串)，用户自定义属性[属性名称 =>  属性值]，以及添加用户自定义的javascript代码（['script'=>'js代码']，注意：js代码不需要<script>标签）;
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * 创建“添加”按钮
     */
    protected function createBtn($options = [])
    {
        $href = 'javascript:void(0)';
        # 背景色
        isset($options['style']) && $options['style']
            ? $style = $this->makeStyle($options['style'])
            : $style = 'color:#ffffff;background:#00BFFF;';
        # 选中颜色
        isset($options['hover_style']) && $options['hover_style']
            ? $hover_style = $this->makeStyle($options['hover_style'])
            : $hover_style = 'color:#fff;background:#1E90FF;';
        # 点击后的颜色
        isset($options['visited_style']) && $options['visited_style']
            ? $visited_style = $this->makeStyle($options['visited_style'])
            : $visited_style = '';
        # 自定义按钮属性
        isset($options['attributes']) && $options['attributes']
            ? $attributes = $this->makeAttributes($options['attributes'])
            : $attributes = '';
        # 按钮位置，right 或者 left
        isset($options['position']) && $options['position']
            ? $position = $options['position']
            : $position = 'right';
        # 点击事件
        isset($options['disableClickEvent']) && $options['disableClickEvent']
            ? $disableClickEvent = 1
            : $disableClickEvent = 0;
        # 前缀
        isset($options['_prefix'])
            ? $_prefix = $options['_prefix']
            : $_prefix = 'test';
        # 自定义标签
        isset($options['tags'])
            ? $tags = $options['tags']
            : $tags = '';
        # javascript代码段
        isset($options['script'])
            ? $script = $options['script']
            : $script = '';
        # 跳转url
        isset($options['url'])
            ? $url = $href = $options['url']
            : $url = '';
        # 打开窗口属性
        isset($options['_target'])
            ? $_target = $options['_target']
            : $_target = '';
        # 按钮文本
        isset($options['text'])
            ? $text = $options['text']
            : $text = '新增';
        # 获取选中的id
        isset($options['selectRows']) && $options['selectRows']
            ? $selectRows = 1
            : $selectRows = 0;
        return <<<EOT
<a data-href="{$href}" target="{$_target}" class="{$_prefix}-wen-create-aa">
    <label class="btn btn-default btn-sm {$_prefix}-wen-create-btn" {$attributes} data-url="{$url}">{$text}</label>
</a>
{$tags}
<script>
    document.querySelector('.{$_prefix}-wen-create-btn').addEventListener('click', function (event) {
        event.preventDefault();
        event.target.classList.add('{$_prefix}-click-status');
        if (!{$disableClickEvent}){
            var url = event.target.dataset.url;
            if (url) {
                if ({$selectRows}) {
                    var ids = selectRows();
                    if (url.indexOf('?') < 0) {
                        url += '?ids=' + ids.join(',');
                    } else {
                        url += '&ids=' + ids.join(',');
                    }
                }
                document.querySelector('.{$_prefix}-wen-create-aa').href = url;
                document.querySelector('.{$_prefix}-wen-create-aa').click();
            }
        }
    });
    function getBtnElement() {
        return document.querySelector('.{$_prefix}-wen-create-btn');
    }
    {$script}
    function selectRows() {
        var ids = new Array();
        var objs = document.querySelectorAll('.grid-row-checkbox:checked');
        if (objs) {
            objs.forEach(function (item, index) {
                ids.push(item.getAttribute('data-id'));
            })
        }
        return ids;
    }
</script>
<style>
    .{$_prefix}-wen-create-aa{
        margin: 0px 5px;
        float: {$position};
    }
    .{$_prefix}-wen-create-btn {
        {$style}
    }
    .{$_prefix}-wen-create-btn:hover {
        {$hover_style}
    }
    .{$_prefix}-click-status {
        {$visited_style}
    }
</style>
EOT;
//        return view('tools.createbtn', $options);
    }

    /**
     * @param $style
     * @return string
     * 处理用户自定义样式
     */
    protected function makeStyle($style)
    {
        if (is_array($style)) {
            $str = '';
            foreach ($style as $index => $item) {
                $str .= "$index: $item;";
            }
            return $str;
        } else {
            return $style;
        }
    }

    /**
     * @param $attributes
     * @return string
     * 处理用户自定义属性
     */
    protected function makeAttributes($attributes)
    {
        if (is_array($attributes)) {
            $attr_str = '';
            foreach ($attributes as $name => $val) {
                $attr_str .= ' ' . $name . '=' . $val;
            }
            return $attr_str;
        } else {
            return $attributes;
        }
    }

}