<?php

namespace Appstract\Stock\Tests;

use Illuminate\Support\Facades\DB;

class QueryScopeTest extends TestCase
{
    /** @test */
    public function it_can_check_in_stock()
    {
        $queryEmpty = StockModel::whereInStock()->count();

        $this->stockModel->setStock(10);

        $queryResult = StockModel::whereInStock()->count();

        $this->assertEquals(0, $queryEmpty);
        $this->assertEquals(1, $queryResult);
    }

    /** @test */
    public function it_can_check_out_of_stock()
    {
        $queryResult = StockModel::whereOutOfStock()->count();

        $this->stockModel->setStock(10);

        $queryEmpty = StockModel::whereOutOfStock()->count();

        $this->assertEquals(1, $queryResult);
        $this->assertEquals(0, $queryEmpty);
    }

    /** @test */
    public function it_can_check_out_of_stock_when_zero()
    {
        $queryEmpty = StockModel::whereInStock()->count();
        $queryResult = StockModel::whereOutOfStock()->count();

        $this->assertEquals(0, $queryEmpty);
        $this->assertEquals(1, $queryResult);
    }

    /** @test */
    public function it_can_check_that_a_warehouse_is_in_stock()
    {
        $queryResult = StockWithWarehouseModel::whereOutOfStock()->count();

        $this->stockWithWarehouseModel->setStock(10, ['warehouse' => 1]);

        $queryNotEmpty = StockWithWarehouseModel::warehouse(1)->whereInStock()->count();

        $this->assertEquals(1, $queryResult);
        $this->assertEquals(1, $queryNotEmpty);
    }

    /** @test */
    public function it_can_check_that_a_warehouse_is_out_of_stock()
    {
        $queryResult = StockWithWarehouseModel::whereOutOfStock()->count();

        $this->stockWithWarehouseModel->setStock(10, ['warehouse' => 1]);
        $this->stockWithWarehouseModel->setStock(0, ['warehouse' => 2]);

        $queryEmpty = StockWithWarehouseModel::warehouse(2)->whereOutOfStock()->count();

        $this->assertEquals(1, $queryResult);
        $this->assertEquals(0, $queryEmpty);
    }
}
