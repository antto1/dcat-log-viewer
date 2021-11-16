<?php

namespace Antto\LogViewer\Repositories;

use Dcat\Admin\Grid;
use Dcat\Admin\Repositories\Repository;
use Illuminate\Pagination\LengthAwarePaginator;

class LogFile extends Repository
{
    public $data;

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * Grid data
     *
     * @param Grid\Model $model
     * @return LengthAwarePaginator
     */
    public function get(Grid\Model $model)
    {
        return $this->data;
    }
}
