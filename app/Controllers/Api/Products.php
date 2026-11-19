<?php

namespace App\Controllers\Api;

class Products extends CatalogController
{
    protected function catalog(): array
    {
        return ['table' => 'products', 'type' => 'product', 'label' => 'Product'];
    }
}
