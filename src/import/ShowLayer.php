<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/23
 * Time: 14:49
 */

namespace Wenruns\Excel\import;


class ShowLayer
{

    protected $title = '提示框'; // 提示标题

    protected $content = ''; // 提示内容

    protected $showConfirmButton = true; // 显示确认按钮

    protected $confirmButtonText = '确定';  // 确认按钮文本

    protected $confirmButtonBackground = '#3085d6'; // 确认按钮背景颜色

    protected $showCancelButton = true; // 显示取消按钮

    protected $cancelButtonText = '取消'; // 取消按钮文本

    protected $cancelButtonBackground = '#aaa'; // 取消按钮背景颜色

    protected $callback = 'function(){}'; // 按钮点击事件

    protected $width = '0px';

    protected $maxHeight = '80vh';

    protected $iconType = 'info';


    public function __construct($options = [])
    {
        $this->initData($options);
    }

    public function then($callback = '')
    {
        $this->callback = $callback;
        return $this;
    }


    public function render()
    {
        echo $this->showLayer();
    }

    protected function initData($options)
    {
        isset($options['title']) ? $this->title = $options['title'] : '';
        isset($options['content']) ? $this->content = $options['content'] : '';
        isset($options['showConfirmButton']) ? $this->showConfirmButton = $options['showConfirmButton'] : '';
        isset($options['confirmButtonText']) ? $this->confirmButtonText = $options['confirmButtonText'] : '';
        isset($options['confirmButtonBackground']) ? $this->confirmButtonBackground = $options['confirmButtonBackground'] : '';
        isset($options['showCancelButton']) ? $this->showCancelButton = $options['showCancelButton'] : '';
        isset($options['cancelButtonText']) ? $this->cancelButtonText = $options['cancelButtonText'] : '';
        isset($options['cancelButtonBackground']) ? $this->cancelButtonBackground = $options['cancelButtonBackground'] : '';
        isset($options['width']) ? $this->width = $options['width'] : '';
        isset($options['height']) ? $this->maxHeight = $options['height'] : '';
        isset($options['type']) ? $this->iconType = $options['type'] : '';
    }


    protected function showLayer()
    {
        $type = $this->iconType;
        return <<<EOT
<section class="wen-tips-layer-box">
    <style>
        .wen-tips-bg{
            position: fixed;
            top: 0px;
            left: 0px;
            z-index: 999;
            background: #000;
            opacity: 0.5;
            width: 100vw;
            height: 100vh;
            display: none;
        }
        .wen-tips-layer{
            position: fixed;
            z-index: 1000;
            top: 0px;
            left: 0px;
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: transparent;
        }
        .wen-tips-box{ 
            will-change: transfrom; 
            background: white;
            border-radius: 5px;
            box-sizing: border-box;
            padding: 5px 10px;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
            -ms-transition: all 0.5s;
            -o-transition: all 0.5s;
            transition: all 0.5s;
            -webkit-transform: scale(0, 0);
            -moz-transform: scale(0, 0);
            -ms-transform: scale(0.1, 0);
            -o-transform: scale(0, 0);
            transform: scale(0, 0);
            min-width: 400px;
            width: {$this->width};
        }
        .wen-tips-title{
            display: block;
            width: 100%;
            height: 50px;
            line-height: 50px;
            font-size: 20px;
            text-align: center;
        }
        .wen-tips-content{
            display: block;
            width: 100%;
            text-align: center;
             overflow: auto;
             max-width: 90vw;
             max-height: {$this->maxHeight};
        }
        .wen-tips-button{
            text-align: center;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
    <script >
        
        let handle = setInterval(function() {
            let boxObj = document.querySelector('.wen-tips-box');
            if(boxObj){
                document.querySelector('.wen-tips-bg').style.display = 'block';
                boxObj.style.transform = 'scale(1,1)';
                boxObj.style['-webkit-transform'] = 'scale(1,1)';
                boxObj.style['-moz-transform'] = 'scale(1,1)';
                boxObj.style['-ms-transform'] = 'scale(1,1)';
                boxObj.style['-o-transform'] = 'scale(1,1)';
                clearInterval(handle)
            }
        }, 100)
    </script>
    <section class="wen-tips-bg"></section>
    <section class="wen-tips-layer">
        <div class="wen-tips-box">
            {$this->showIcon()}
            <div class="wen-tips-title">{$this->title}</div>
            <content class="wen-tips-content">{$this->content}</content>
            <div class="wen-tips-button">
                {$this->sureButton()}
                {$this->cancelButton()}
            </div>      
        </div>
    </section>
</section>
EOT;
    }

    protected function sureButton()
    {
        if (!$this->showConfirmButton) {
            return '';
        }
        $callback = $this->callback;
        return <<<EOT
<style>
    .wen-confirm-button{
        cursor: pointer;
        padding: 3px 20px;
        color: white;
        background: {$this->confirmButtonBackground};
    }
</style>
<script> 
    function confirm() {
        let callback = $callback;
        if (typeof callback == 'function') {
            callback({value: true});
        }
        document.querySelector('.wen-tips-layer-box').remove();
    }
</script>
<button class="wen-confirm-button" onclick="confirm()">{$this->confirmButtonText}</button>
EOT;
    }


    protected function cancelButton()
    {
        if (!$this->showCancelButton) {
            return '';
        }
        $callback = $this->callback;
        return <<<EOT
<style>
    .wen-cancel-button{
        cursor: pointer;
        margin-left: 20px;
        padding: 3px 20px;
        color: white;
        background: {$this->cancelButtonBackground};
    }        
</style>
<script>
    function cancel(){
        let callback = $callback;
        if(typeof callback == 'function'){
            callback({value:false});
        }
        document.querySelector('.wen-tips-layer-box').remove();
    }
</script>
<button class="wen-cancel-button" onclick="cancel()">{$this->cancelButtonText}</button> 
EOT;
    }

    protected function showIcon()
    {
        if (empty($this->iconType)) {
            return '';
        }
        switch ($this->iconType) {
            case 'warning':
                $border_color = '#facea8';
                $font_color = '#f8bb86';
                $icon_content = $this->warningIcon();
                break;
            case 'success':
                $border_color = 'rgba(165, 220, 134, 0.3)';
                $font_color = 'rgba(165, 220, 134, 0.3)';
                $icon_content = $this->successIcon();
                break;
            case 'error':
                $border_color = '#f27474';
                $font_color = '#f27474';
                $icon_content = $this->errorIcon();
                break;
            case 'info':
            default:
                $border_color = '#9de0f6';
                $font_color = '#3fc3ee';
                $icon_content = $this->infoIcon();
        }
        return <<<EOT
<style>
    .wen-tips-icon{
        width: 70px;
        height: 70px;
        -webkit-border-radius: 50%;
        -moz-border-radius: 50%;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 10px auto;
        position: relative;
        border: 4px solid {$border_color};
        color: {$font_color};
 }
    .wen-tips-icon span{
        font-size: 45px;
    }
</style>
<div class="wen-tips-icon">
    {$icon_content}
</div>
EOT;

    }

    private function errorIcon()
    {
        return <<<HTML
<style>
    .icon-error{
        position: absolute;
        height: 30px;
        width: 0px;
        border-right: 4px solid;
        display: block;
    }
    .icon-error-line-left{
        -webkit-transform: rotate(45deg);
        -moz-transform: rotate(45deg);
        -ms-transform: rotate(45deg);
        -o-transform: rotate(45deg);
        transform: rotate(45deg);
    }
    .icon-error-line-right{
        -webkit-transform: rotate(-45deg);
        -moz-transform: rotate(-45deg);
        -ms-transform: rotate(-45deg);
        -o-transform: rotate(-45deg);
        transform: rotate(-45deg);
    }
</style>
<span class="icon-error icon-error-line-left"></span>
<span class="icon-error icon-error-line-right"></span>
HTML;

    }

    private function infoIcon()
    {
        return '<span>i</span>';
    }

    private function warningIcon()
    {
        return '<span>!</span>';
    }

    private function successIcon()
    {
        return <<<THML
<style>
.icon-success{
    display: block;
    -webkit-transform: rotate(45deg);
    -moz-transform: rotate(45deg);
    -ms-transform: rotate(45deg);
    -o-transform: rotate(45deg);
    transform: rotate(45deg);
    border-color: rgba(165, 220, 134, 0.3);
    position: absolute;
}
.icon-line-bottom{
    width: 17px;
    border-bottom: solid;
    top: 34px;
    left: 15px;
}
.icon-line-right{
    height: 34px;
    border-right: solid;
    top: 12px;
    left: 39px;
}
.icon-success-transition{
    position: absolute;
    top: -4px;
    left: -4px;
    width: 70px;
    height: 70px;
    
    
    -webkit-border-radius: 50%;
    -moz-border-radius: 50%;
    border-radius: 50%;
    border: 4px solid;
    border-bottom-color: #a5dc86;

    -webkit-transition: all 0.5s;
    -moz-transition: all 0.5s;
    -ms-transition: all 0.5s;
    -o-transition: all 0.5s;
    transition: all 0.5s;
    will-change: tranform;
    -webkit-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
}
</style>

<span class="icon-success icon-line-bottom"></span>
<span class="icon-success icon-line-right"></span>
<div class="icon-success-transition"></div>
<script >
    function getTransitionEvent() {
        let el = document.createElement('surface');
        let transitions = {
         'transition':'transitionend',
         'OTransition':'oTransitionEnd',
         'MozTransition':'transitionend',
         'WebkitTransition':'webkitTransitionEnd'
       }
       for(let t in transitions){
           if(el.style[t] !== undefined){
               return transitions[t];
           } 
       }
       return false;
    }
    let transitionEvent = getTransitionEvent();
    let obj = document.querySelector('.icon-success-transition');
    obj.addEventListener(transitionEvent, function(e) {
        obj.remove();
        let n  = 0;
        let intervalHandle = setInterval(function() {
            let obj = null;
            if(n==0){
                obj = document.querySelector('.icon-line-bottom');
                if(obj){
                    obj.style['border-color'] = '#a5dc86';
                }
            }else if(n==1){
                obj = document.querySelector('.icon-line-right');
                if(obj){
                    obj.style['border-color'] = '#a5dc86';
                }
            }else{
                clearInterval(intervalHandle);
            }
            if(obj){
                n++;
            }
        }, 150)
    });
    let timeoutHandle = setTimeout(function() {
        obj.style.transform = 'rotate(-235deg)';
        obj.style['-webkit-transform'] = 'rotate(-235deg)';
        obj.style['-moz-transform'] = 'rotate(-235deg)';
        obj.style['-ms-transform'] = 'rotate(-235deg)';
        obj.style['-o-transform'] = 'rotate(-235deg)';
    },500)
</script>
THML;
    }

}