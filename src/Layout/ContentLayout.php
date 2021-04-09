<?php
/**
 * Created by PhpStorm.
 * User: Administrator【wenruns】
 * Date: 2021/1/7
 * Time: 18:32
 */

namespace App\Admin\Services\Excel\Layout;


use Encore\Admin\Layout\Content;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\View;
use Illuminate\Contracts\View\View as ViewContract;

class ContentLayout extends Content
{

    protected $_disableBreadcrumb = false;

    protected $_disableHeader = false;

    protected $_disableDescription = false;

    protected $_disableFooter = false;

    protected $_factory = null;

    protected $_compilerEngine = null;

    protected $_viewRootPath = __DIR__ . '/../views/';

    protected $_viewExtend = '.blade.php';

    protected $_styleCode = '';

    /**
     * @return \Illuminate\View\Factory
     */
    public function getFactory()
    {
        if (empty($this->_factory)) {
            $engineResolver = app(EngineResolver::class);
            $fileSystem = app(Filesystem::class);
            $fileViewFinder = new FileViewFinder($fileSystem, [
                'wen-excel' => __DIR__ . '/../views/',
            ]);
            $dispatcher = app(Dispatcher::class);
            $this->_factory = app(Factory::class, [$engineResolver, $fileViewFinder, $dispatcher]);
        }
        return $this->_factory;
    }

    public function getCompilerEngine()
    {
        if (empty($this->_compilerEngine)) {
            $bladeCompiler = app(BladeCompiler::class, [__DIR__ . '/../Caches/Views']);
//        $bladeCompiler = new BladeCompiler($fileSystem, __DIR__ . '/Caches/Views');
//        $bladeCompiler->setPath(__DIR__ . '/views');
            $this->_compilerEngine = new CompilerEngine($bladeCompiler);
        }
        return $this->_compilerEngine;
    }

    /**
     * @param $view
     * @param $data
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function viewParse($view, $data)
    {
        $path = $this->getViewPath($view = $this->formatView($view));
        $viewer = new View($this->getFactory(), $this->getCompilerEngine(), $view, $path, $data);
        return tap($viewer, function ($view) {
            $this->callComposer($view);
        });
    }

    protected function formatView($view)
    {
        return str_replace('.', '/', $view);
    }

    protected function getViewPath($view)
    {
        if (strpos(str_replace('\\', '/', $view), ':/') === false) {
            return $this->_viewRootPath . $view . $this->_viewExtend;
        }
        return $view;
    }

    /**
     * Call the composer for a given view.
     *
     * @param  \Illuminate\Contracts\View\View $view
     * @return void
     */
    public function callComposer(ViewContract $view)
    {
        $this->getFactory()->getDispatcher()->dispatch('composing: ' . $view->name(), [$view]);
    }


    public function displayBreadcrumb($disable = true)
    {
        $this->_disableBreadcrumb = $disable;
        return $this;
    }

    public function displayHeader($disable = true)
    {
        $this->_disableHeader = $disable;
        return $this;
    }

    public function disableDescription($disable = true)
    {
        $this->_disableDescription = $disable;
        return $this;
    }

    public function disableFooter($disable = true)
    {
        $this->_disableFooter = $disable;
        return $this;
    }

    public function setStyleYourself($styleCode)
    {
        $this->_styleCode = $styleCode;
        return $this;
    }


    public function render()
    {
        $items = [
            'header'          => $this->title,
            'description'     => $this->description,
            'breadcrumb'      => $this->breadcrumb,
            '_content_'       => $this->build(),
            '_view_'          => $this->view,
            '_user_'          => $this->getUserData(),

//            'header' => $this->title,
//            'description' => $this->description,
//            'breadcrumb' => $this->breadcrumb,
//            'content' => $this->build(),
            'hideBreadcrumb'  => $this->_disableBreadcrumb,
            'hideHeader'      => $this->_disableHeader,
            'hideDescription' => $this->_disableDescription,
            'hideFooter'      => $this->_disableFooter,
            'includeView'     => $this->setIncludeView(),
            'styleCode'       => $this->_styleCode,
        ];
//        dd(11, new EngineResolver(), new CompilerEngine(new BladeCompiler(new Filesystem(), __DIR__.'/Caches/Views')));

        return $this->viewParse('content', $items)->render();
//        return $this->view(__DIR__ . '/views/content.blade.php', $items)->render();
    }


    protected function setIncludeView()
    {
        return function ($view, $data = []) {
            $data['includeView'] = $this->setIncludeView();
            return $this->viewParse($view, $data)->render();
        };
    }

    public function row($content)
    {
        if ($content instanceof \Closure) {
            $row = new Row();
            call_user_func($content, $row);
            $this->addRow($row);
        } else {
            $this->addRow(new Row($content));
        }

        return $this;
    }

    public function tab(array $navs, $clickEvent = null, $onBefore = null, $onAfter = null)
    {
        $this->addRow(new Row($navs, true, $clickEvent, $onBefore, $onAfter));
        return $this;
    }

    public function body($content)
    {
        return $this->row($content);
    }

}