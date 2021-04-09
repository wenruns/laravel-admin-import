<?php
/**
 * Created by PhpStorm.
 * User: Administrator【wenruns】
 * Date: 2021/1/15
 * Time: 16:43
 */

namespace App\Admin\Services\Excel\Layout;

use Encore\Admin\Layout\Row as RootRow;

class Row extends RootRow
{

    public function __construct($content = '', $isNav = false, $clickEvent = null, $onBefore = null, $onAfter = null)
    {
        if (!empty($content)) {
            $this->column(12, $content, $isNav, $clickEvent, $onBefore, $onAfter);
        }
    }

    public function column($width, $content, $isNavs = false, $clickEvent = null, $onBefore = null, $onAfter = null)
    {
        $width = $width < 1 ? round(12 * $width) : $width;

        $column = new Column($content, $width, $isNavs, $clickEvent, $onBefore, $onAfter);

        $this->addColumn($column);
    }


    public function build()
    {
        $this->startRow();

        foreach ($this->columns as $column) {
            $column->build();
        }

        $this->endRow();
    }
}