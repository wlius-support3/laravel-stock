<?php

namespace Appstract\Stock;

use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

trait HasStock
{
    /*
     |--------------------------------------------------------------------------
     | Accessors
     |--------------------------------------------------------------------------
     */

    /**
     * Stock accessor.
     *
     * @return int
     */
    public function getStockAttribute()
    {
        return $this->stock();
    }

    /*
     |--------------------------------------------------------------------------
     | Methods
     |--------------------------------------------------------------------------
     */

    public function stock($date = null, $warehouse = null)
    {
        $date = $date ?: Carbon::now();

        if (! $date instanceof DateTimeInterface) {
            $date = Carbon::create($date);
        }

        return (int) $this->stockMutations()
            ->where('created_at', '<=', $date->format('Y-m-d H:i:s'))
            ->when(($this->warehouseEnabled() && $warehouse), function($query) use ($warehouse) {
                return $query->where('warehouse_id', $warehouse);
            })
            ->sum('amount');
    }

    public function increaseStock($amount = 1, $arguments = [])
    {
        return $this->createStockMutation($amount, $arguments);
    }

    public function decreaseStock($amount = 1, $arguments = [])
    {
        return $this->createStockMutation(-1 * abs($amount), $arguments);
    }

    public function mutateStock($amount = 1, $arguments = [])
    {
        return $this->createStockMutation($amount, $arguments);
    }

    public function clearStock($newAmount = null, $arguments = [])
    {
        $this->stockMutations()->delete();

        if (! is_null($newAmount)) {
            $this->createStockMutation($newAmount, $arguments);
        }

        return true;
    }

    public function setStock($newAmount, $arguments = [])
    {
        $warehouse = Arr::get($arguments, 'warehouse');
        $currentStock = $this->stock(null, $warehouse);

        if ($deltaStock = $newAmount - $currentStock) {
            return $this->createStockMutation($deltaStock, $arguments);
        }
    }

    public function inStock($amount = 1, $warehouse = null)
    {
        return $this->stock(null, $warehouse) > 0 && $this->stock(null, $warehouse) >= $amount;
    }

    public function outOfStock($warehouse = null)
    {
        return $this->stock(null, $warehouse) <= 0;
    }

    public function warehouseEnabled()
    {
        return $this->hasWarehouse;
    }

    /**
     * Function to handle mutations (increase, decrease).
     *
     * @param  int $amount
     * @param  array  $arguments
     * @return bool
     */
    protected function createStockMutation($amount, $arguments = [])
    {
        $reference = Arr::get($arguments, 'reference');
        $warehouse = Arr::get($arguments, 'warehouse');

        $createArguments = collect([
            'amount' => $amount,
            'description' => Arr::get($arguments, 'description'),
        ])->when($this->warehouseEnabled(), function ($collection) use ($warehouse){
            return $collection
                ->put('warehouse_id', $warehouse);
        })->when($reference, function ($collection) use ($reference) {
            return $collection
                ->put('reference_type', $reference->getMorphClass())
                ->put('reference_id', $reference->getKey());
        })->toArray();

        return $this->stockMutations()->create($createArguments);
    }

    /*
     |--------------------------------------------------------------------------
     | Scopes
     |--------------------------------------------------------------------------
     */

    public function scopeWhereInStock($query)
    {
        return $query->where(function ($query) {
            return $query->whereHas('stockMutations', function ($query) {
                return $query->select('stockable_id')
                    ->groupBy('stockable_id')
                    ->havingRaw('SUM(amount) > 0');
            });
        });
    }

    public function scopeWhereOutOfStock($query)
    {
        return $query->where(function ($query) {
            return $query->whereHas('stockMutations', function ($query) {
                return $query->select('stockable_id')
                    ->groupBy('stockable_id')
                    ->havingRaw('SUM(amount) <= 0');
            })->orWhereDoesntHave('stockMutations');
        });
    }

    public function scopeWarehouse($query, $warehouse)
    {
        return $query->where(function ($query) use ($warehouse) {
            return $query->whereHas('stockMutations', function ($query) use ($warehouse) {
                return $query->where('warehouse_id', $warehouse);
            })->orWhereDoesntHave('stockMutations');
        });
    }

    /*
     |--------------------------------------------------------------------------
     | Relations
     |--------------------------------------------------------------------------
     */

    /**
     * Relation with StockMutation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\morphMany
     */
    public function stockMutations()
    {
        return $this->morphMany(StockMutation::class, 'stockable');
    }
}
