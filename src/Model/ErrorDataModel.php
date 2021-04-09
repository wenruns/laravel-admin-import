<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/12/24
 * Time: 9:07
 */

namespace App\Admin\Services\Excel\Model;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;

class ErrorDataModel extends Model
{

    protected $table = 'error_data_model';

    protected $_sourceData = [];

    protected $_expandFilter = false;

    public function ifExpandFilter(\Closure $closure)
    {
        $this->checkFilter();
        if ($this->_expandFilter) {
            call_user_func($closure);
        }
        return $this;
    }

    public function paginate()
    {
        if (!$this->_expandFilter) {
            $this->checkFilter();
        }
        $perPage = Request::get('per_page', 20);
        $page = Request::get('page', 1);
        $export = Request::get('_export_', null);
        $total = count($this->_sourceData);
        $datas = array_chunk($this->_sourceData, $perPage);
        if (empty($export)) {
            $data = $datas[$page - 1] ?? [];
        } else {
            if ($export == 'all') {
                $data = $this->_sourceData;
            } else if ($export == 'page:all') {
                $pageRange = Request::get('pageRange', []);
                $start = $pageRange['start'] ?? 1;
                $end = $pageRange['end'] ?? 1;
                $data = [];
                while ($start <= $end) {
                    $data = array_merge($data, $datas[$start - 1]);
                    $start++;
                }
            } else if (strpos($export, 'page:') === false) {
                $data = array_merge([], $this->_sourceData);
            } else {
                $exportPage = explode(':', $export)[1];
                $data = $datas[$exportPage - 1] ?? [];
            }
        }
        extract($data);
        $collects = static::hydrate($data);
        $paginator = new LengthAwarePaginator($collects, $total, $perPage);
        $paginator->setPath(url()->current());
        return $paginator;

    }

    protected function checkFilter()
    {
        $columns = [
            [
                'column'   => 'apply_id',
                'operator' => 'like',
            ], [
                'column'   => 'bank_loan_id',
                'operator' => 'like',
            ], [
                'column'   => 'name',
                'operator' => 'like',
            ], [
                'column'   => 'memo',
                'operator' => 'like',
            ], [
                'column'   => 'attribution_month',
                'operator' => 'between',
            ], [
                'column'   => 'money_amount',
                'operator' => '=',
            ], [
                'column'   => 'type',
                'operator' => 'in',
            ]
        ];
        foreach ($columns as $key => $item) {
            $index = $item['column'];
            if ($index == 'type') {
                $index = 'money_type';
            }
            $value = \request($index);
            if (is_array($value) && empty(array_filter($value, function ($item) {
                    return !empty($item);
                }))) {
                continue;
            }
            if (!empty($value)) {
                if($index == 'attribution_month'){
                    $value['start'] && $value['start'] = strtotime($value['start']);
                    $value['end'] && $value['end'] = strtotime($value['end']);
                }
                $this->where($item['column'], $item['operator'], $this->filterValue($value, $item));
            }
        }
        return $this;
    }

    protected function filterValue($value, $item)
    {
        $this->_expandFilter = true;
        switch ($item['operator'] ?? '') {
            case 'like':
                $value = '%' . $value . '%';
                break;
            default:
        }
        return $value;
    }


    /**
     * @param \Closure|string $column
     * @param string $operator
     * @param null $value
     * @return $this
     */
    public function where($column, $operator = '=', $value = null)
    {
        if (is_callable($column)) {
            call_user_func($column, $this);
            return $this;
        }
        if ($column instanceof Expression) {
            $column = $column->getValue();
        }
        $pointPos = strrpos($column, '.');
        $column = $pointPos === false ? $column : substr($column, $pointPos + 1);
        $this->_sourceData = array_filter($this->_sourceData, function ($item) use ($column, $operator, $value) {
            $val = $item[$column];
            if (empty($value)) {
                return $value == $val;
            } else {
                switch ($operator) {
                    case '=':
                        $res = $value == $val;
                        break;
                    case 'like':
                        $res = $this->likeFilter($value, $val);
                        break;
                    case 'not like':
                        $res = $this->likeFilter($value, $val, true);
                        break;
                    case '<':
                        $res = $val < $value;
                        break;
                    case '>':
                        $res = $val > $value;
                        break;
                    case '<=':
                        $res = $val <= $value;
                        break;
                    case '>=':
                        $res = $val >= $value;
                        break;
                    case 'in':
                        $res = in_array($val, $value);
                        break;
                    case 'not in':
                        $res = !in_array($val, $value);
                        break;
                    case 'between':
                        $res = $this->betweenFilter($value, $val);
                        break;
                    case 'not between':
                        $res = $this->betweenFilter($value, $val, true);
                        break;
                    default:
                        $res = eval($val . $operator . $value);
                }
                return $res;
            }
        });
        return $this;
    }

    /**
     * @param $value
     * @param $itemValue
     * @param bool $isNot
     * @return bool
     */
    protected function betweenFilter($value, $itemValue, $isNot = false)
    {
        if ($isNot) {
            $start = $value['start'] ?? ($value[0] ?? null);
            $end = $value['end'] ?? ($value[1] ?? null);
            if (empty($start) && empty($end)) {
                $res = true;
            } else if ($start and $end) {
                $res = $itemValue <= $start && $itemValue >= $end;
            } else if ($start) {
                $res = $itemValue <= $start;
            } else {
                $res = $itemValue >= $end;
            }
        } else {
            $start = $value['start'] ?? ($value[0] ?? null);
            $end = $value['end'] ?? ($value[1] ?? null);
            if (empty($start) && empty($end)) {
                $res = true;
            } else if ($start and $end) {
                $res = $itemValue >= $start && $itemValue <= $end;
            } else if ($start) {
                $res = $itemValue >= $start;
            } else {
                $res = $itemValue <= $end;
            }
        }
        return $res;
    }

    /**
     * @param $value
     * @param $itemValue
     * @param bool $isNot
     * @return bool
     */
    protected function likeFilter($value, $itemValue, $isNot = false)
    {
        if (strrpos($value, '%') === false) {
            return $isNot ? ($value != $itemValue) : ($value == $itemValue);
        }
        $values = explode('%', $value);
        $pos = 0;
        $len = count($values);
        foreach ($values as $key => $item) {
            if ($key == 0 && !empty($item)) {
                $pos = strpos($itemValue, $item, $pos);
                if ($pos === false || $pos > 0) {
                    return $isNot ? true : false;
                }
            } else if ($key == ($len - 1) && !empty($item)) {
                if (mb_substr($itemValue, -mb_strlen($item)) != $item) {
                    return $isNot ? true : false;
                }
            } else if (!empty($item)) {
                $pos = strpos($itemValue, $item, $pos);
                if ($pos === false) {
                    return $isNot ? true : false;
                }
            }
        }
        return $isNot ? false : true;
    }

    /**
     * @param $column
     * @param $values
     * @return $this
     */
    public function whereIn($column, $values)
    {
        $column = substr($column, strrpos($column, '.') + 1);
        $this->_sourceData = array_filter($this->_sourceData, function ($item) use ($column, $values) {
            return in_array($item[$column], $values) ? true : false;
        });
        return $this;
    }


    /**
     * @param $data
     * @return $this
     */
    public function sourceData($data)
    {
        if (empty($data)) {
            return $this;
        }
        $this->_sourceData = array_merge(...$data);
        array_walk($this->_sourceData, function (&$value, $key) {
            $value = array_merge($value, ['id' => $key + 1]);
        });
        return $this;
    }

    /**
     * @param array|string $relations
     * @return ErrorDataModel|\Illuminate\Database\Eloquent\Builder|Model
     */
    public static function with($relations)
    {
        return new static;
    }


}