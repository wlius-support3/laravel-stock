<?php

namespace Appstract\Stock\Tests;

use Appstract\Stock\HasStock;
use Appstract\Stock\HasWarehouse;
use Illuminate\Database\Eloquent\Model;

class StockWithWarehouseModel extends Model
{
    use HasStock;
    use HasWarehouse;

    protected $table = 'stock_models';

    protected $guarded = [];

    public $timestamps = false;
}
