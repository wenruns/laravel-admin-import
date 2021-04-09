<?php
/**
 * Created by PhpStorm.
 * User: Administrator【wenruns】
 * Date: 2021/1/9
 * Time: 10:10
 */

namespace App\Admin\Services\Excel\Layout;


use Encore\Admin\Facades\Admin;

class Button
{
    /**
     * @var bool
     */
    protected $_hadCreateBtn = false;

    /**
     * @var int|string
     */
    protected $_unique = '';

    /**
     * @var string
     */
    protected $_spacer = '';

    /**
     * @var array
     */
    protected $_buttons = [];

    /**
     * @var array
     */
    protected $_clickEvents = [];

    /**
     * @var int
     */
    protected $_n = 1;

    /**
     * @var array
     */
    protected $_js = [];

    protected $_scripts = [];


    /**
     * Button constructor.
     * @param array $buttons
     * @param string $spacer
     */
    public function __construct($buttons = [], $spacer = '')
    {
        $this->_buttons = $buttons;
        $this->_spacer = $spacer;
        $this->_unique = mt_rand(1000, 9999);
    }

    /**
     * 设置js点击事件
     */
    protected function setScript()
    {
        $unique = $this->_unique;
        $clickEvents = json_encode($this->_clickEvents);
        $script = <<<SCRIPT
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
$(".wen-button-$unique").click(function(e){
    var clickEvents = $clickEvents;
    var func = clickEvents[e.currentTarget.dataset.sign];
    if(func){
        func = "var fn = " + func;
        eval(func);
        fn.call(this, e);
    }
});
SCRIPT;
        Admin::script($script);
    }

    /**
     * 生成按钮html
     * @return string
     */
    protected function build()
    {
        $html = '';
        if (isset($this->_buttons[0])) {
            foreach ($this->_buttons as $key => $item) {
                $html .= $this->createBtn($item) . $this->_spacer;
            }
            $html = rtrim($html, $this->_spacer);
        } else {
            $html .= $this->createBtn($this->_buttons);
        }

        if ($this->_hadCreateBtn) {
            $this->setScript();
            $html = $this->loadJs() . $html . $this->loadJavascript();
        }
        return $html;
    }

    /**
     * 加载javascript代码
     * @return mixed|string
     */
    protected function loadJavascript()
    {
        $scriptElements = '';
        if (empty($this->_scripts)) {
            return $scriptElements;
        }
        foreach ($this->_scripts as $script) {
            $scriptElements .= '<script type="text/javascript">' . $script . '</script>';
        }
        return $scriptElements;
    }

    /**
     * 加载js文件
     * @return string
     */
    protected function loadJs()
    {
        $script = '';
        if (empty($this->_js)) {
            return $script;
        }
        foreach ($this->_js as $path) {
            $script .= '<script type="text/javascript" src="' . $path . '"></script>';
        }
        return $script;
    }

    /**
     * html生成
     * @param $item
     * @return string
     */
    protected function createBtn($item)
    {
        if (!$this->checkShow($item)) {
            return '';
        }
        $this->_hadCreateBtn = true;
        $unique = $this->_unique;
        $buttonText = $this->getItem($item, 'prefix') . $this->buttonText($item) . $this->getItem($item, 'suffix');
        $dataButtonText = strip_tags($buttonText);
        $sign = md5($buttonText);
        $this->filterEvent($item, $sign)->filterJs($item)->filterScript($item);
        $icon = $this->getItem($item, 'icon');
        $iconEle = $icon ? '<i class="fa fa-' . $icon . '" style="margin-right:3px;"></i>' : '';
        return <<<HTML
<style>
    {$this->css($item)}
</style>
<a class="wen-button-{$unique} {$this->getItem($item, 'class', 'btn')}"  {$this->attributes($item)} {$this->attachData($item)} style="{$this->getItem($item, 'style')}"  href="{$this->url($item)}" data-sign="{$sign}" data-url="{$this->url($item)}" data-text="{$dataButtonText}" data-slug="{$this->getItem($item, 'slug')}">{$iconEle}{$buttonText}</a>
HTML;
    }

    protected function filterScript($item)
    {
        if ($script = $this->getItem($item, 'script', null)) {
            $this->addScript($script);
        }
        return $this;
    }

    protected function filterJs($item)
    {
        if ($js = $this->getItem($item, 'js', null)) {
            $this->addJs($js);
        }
        return $this;
    }

    protected function filterEvent($item, $sign)
    {
        if ($clickEvent = $this->getItem($item, 'clickEvent', null)) {
            $this->_clickEvents[$sign] = $this->compressHtml($clickEvent);
        }
        return $this;
    }

    /**
     * dataset设置
     * @param $item
     * @return string
     */
    protected function attachData($item)
    {
        $attachString = '';
        if ($attachData = $this->getItem($item, 'attach', null)) {
            if (is_array($attachData)) {
                foreach ($attachData as $name => $value) {
                    $attachString .= 'data-attach_' . $name . '="' . (is_array($value) ? json_encode($value) : $value) . '" ';
                }
            } else {
                $attachString = 'data-attach="' . $attachData . '"';
            }
        }
        return $attachString;
    }


    /**
     * 获取数组中的元素
     * @param array $arr
     * @param $key
     * @param string $default
     * @return mixed|string
     */
    protected function getItem(array $arr, $key, $default = '')
    {
        if (array_key_exists($key, $arr)) {
            return $arr[$key];
        }
        return $default;
    }

    /**
     * url生成
     * @param $item
     * @return string
     */
    protected function url($item)
    {
        if (isset($item['url']) && isset($item['data'])) {
            $data = $item['data'];
            $path = isset($item['path']) ? $item['path'] : $data->getUri();
            if (strpos($item['url'], '?') === false) {
                $item['url'] .= '?_path=' . urlencode($path);
            } else {
                $item['url'] .= '&_path=' . urlencode($path);
            }
        }
        return isset($item['url']) ? $item['url'] : '#';
    }

    /**
     * 获取按钮文本
     * @param $item
     * @return string
     */
    protected function buttonText($item)
    {
        $buttonText = $this->getItem($item, 'buttonText');
        if (empty($buttonText)) {
            $buttonText = 'button' . $this->_n;
            $this->_n++;
        }
        return $buttonText;
    }

    /**
     * 属性字符串
     * @param $item
     * @return string
     */
    protected function attributes($item)
    {
        $attributes = $this->getItem($item, 'attributes', []);
        if (is_array($attributes)) {
            $string = '';
            foreach ($attributes as $name => $value) {
                $string .= $name . '="' . (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value) . '" ';
            }
            return $string;
        }

        return $attributes;
    }

    /**
     * css设置
     * @param $item
     * @return string
     */
    protected function css($item)
    {
        $css = $this->getItem($item, 'css');
        if (is_array($css)) {
            $cssStr = '';
            foreach ($css as $selector => $item) {
                $cssStr .= $selector . '{';
                foreach ($item as $name => $value) {
                    $cssStr .= $name . ':' . $value . ';';
                }
                $cssStr .= '}';
            }
            return $cssStr;
        }
        return $css;
    }

    /**
     * 检测是否显示
     * @param $item
     * @return bool|mixed|string
     */
    protected function checkShow($item)
    {
        if (empty($item)) {
            return false;
        }
        $show = $this->getItem($item, 'show', true);
        if ($show && $permission = $this->getItem($item, 'permission', null)) {
            return Admin::user()->can($permission);
        }
        return $show;
    }

    /**
     * 输出按钮
     * @return string
     */
    public function render()
    {
        return $this->build();
    }

    /**
     * 压缩html
     * @param $string
     * @return string
     */
    protected function compressHtml($string)
    {
        return ltrim(rtrim(preg_replace(array("/> *([^ ]*) *</", "//", "'/\*[^*]*\*/'", "/\r\n/", "/\n/", "/\t/", '/>[ ]+</'),
            array(">\\1<", '', '', '', '', '', '><'), $string)));
    }

    /**
     * 新增javascript代码
     * @param $script
     * @return $this
     */
    public function addScript($script)
    {
        if (empty($script)) {
            return $this;
        }
        if (is_array($script)) {
            foreach ($script as $key => $item) {
                $index = md5($item);
                array_key_exists($index, $this->_scripts) || $this->_scripts[$index] = $item;
            }
        } else {
            $index = md5($script);
            array_key_exists($index, $this->_scripts) || $this->_scripts[$index] = $script;
        }
        return $this;
    }

    /**
     * 新增js文件
     * @param $js
     * @return $this
     */
    public function addJs($js)
    {
        if (empty($js)) {
            return $this;
        }
        if (is_array($js)) {
            $this->_js = array_merge($this->_js, $js);
        } else {
            in_array($js, $this->_js) || $this->_js[] = $js;
        }
        return $this;
    }

    /**
     * 快捷创建按钮
     * @param array $buttons
     * @param string $spacer
     * @param array $js
     * @param string $scriptCodes
     * @return string
     */
    public static function create(array $buttons, $spacer = '', $js = [], $scriptCodes = '')
    {
        return (new self($buttons, $spacer))->addScript($scriptCodes)->addJs($js)->render();
    }

}