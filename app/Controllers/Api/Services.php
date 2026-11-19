<?php

namespace App\Controllers\Api;

class Services extends CatalogController
{
    protected function catalog(): array
    {
        return ['table' => 'services', 'type' => 'service', 'label' => 'Service'];
    }
}
