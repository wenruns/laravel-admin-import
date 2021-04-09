<?php
/**
 * Created by PhpStorm.
 * User: Administrator【wenruns】
 * Date: 2021/1/15
 * Time: 16:45
 */

namespace App\Admin\Services\Excel\Layout;

use Encore\Admin\Grid;
use Encore\Admin\Layout\Column as RootColumn;
use Illuminate\Contracts\Support\Renderable;

class Column extends RootColumn
{
    public function __construct($content, int $width = 12, $isNav = false, $clickEvent = null, $onBefore = null, $onAfter = null)
    {
        if ($content instanceof \Closure) {
            call_user_func($content, $this);
        } else if ($isNav) {
            $this->append([
                'isNav'      => $isNav,
                'content'    => $content,
                'clickEvent' => $clickEvent,
                'onBefore'   => $onBefore,
                'onAfter'    => $onAfter,
            ]);
        } else {
            $this->append($content);
        }

        ///// set width.
        // if null, or $this->width is empty array, set as "md" => "12"
        if (is_null($width) || (is_array($width) && count($width) === 0)) {
            $this->width['md'] = 12;
        } // $this->width is number(old version), set as "md" => $width
        elseif (is_numeric($width)) {
            $this->width['md'] = $width;
        } else {
            $this->width = $width;
        }
    }

    public function row($content)
    {
        if (!$content instanceof \Closure) {
            $row = new Row($content);
        } else {
            $row = new Row();

            call_user_func($content, $row);
        }

        ob_start();

        $row->build();

        $contents = ob_get_contents();

        ob_end_clean();

        return $this->append($contents);
    }

    public function tab(array $navs)
    {
        $row = (new Row($navs, true));
        ob_start();
        $row->build();
        $contents = ob_get_contents();
        ob_end_clean();
        return $this->append($contents);
    }

    public function build()
    {
        $this->startColumn();

        foreach ($this->contents as $content) {
            if ($content instanceof Renderable || $content instanceof Grid) {
                echo $content->render();
            } else {
                if (is_array($content) && $this->getItems($content, 'isNav')) {
                    $this->showNavs($content);
                } else {
                    echo (string)$content;
                }
            }
        }

        $this->endColumn();
    }

    protected function getItems($arr, $key, $default = null)
    {
        return $arr[$key] ?? $default;
    }

    protected function showNavs($content)
    {
        $navs = json_encode($this->getItems($content, 'content', []));
        $clickEvent = $this->getItems($content, 'clickEvent');
        $onBefore = $this->getItems($content, 'onBefore');
        $onAfter = $this->getItems($content, 'onAfter');
        $id = 'tab-nav-' . mt_rand(100000, 999999);
        echo <<<HTML
<style>
    #{$id} > div:nth-child(1){
        background: #fff;
        border-top: 3px solid #cccccc;
        border-top-left-radius: 3px;
        border-top-right-radius: 3px;
    }
    #{$id} > div:nth-child(2){
        border: 0px !important;
    }
    #{$id} iframe .box{
        border: 0px !important;
    }
</style>
<div id="{$id}"></div>
<script>
    let navList = new ListSet({
        eleId: '{$id}',
        navs: {$navs},
        navScrollHeight: 0,
        iframeHeight: 'auto',
        clickEvent: function(e, data, index){
            let eventFunc = '{$clickEvent}';
            if(eventFunc){
                eval('var fn = ' + eventFunc);
                fn.call(this, e, data, index);
            }
        }, 
        iframeAfterOnLoad:function(data){
            let onAfter = '{$onAfter}';
            if(onAfter){
                eval('var fn = '+onAfter);
                fn.call(this, data);
            }
        }, 
        iframeBeforeOnLoad: function(data){
            let onBefore = '{$onBefore}';
            if(onBefore){
                eval('var fn = '+onBefore);
                fn.call(this, data);
            }
        }
    });
</script>
HTML;
    }
}