<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

// Extra routes only: the resource routes (CRUD, tree reorder) come from the core `gingerminds_crud` loader.
return static function (RoutingConfigurator $routes): void {
    // The block modal of the page content (BlockCanvasType).
    foreach (['form', 'validate'] as $action) {
        $routes->add('gingerminds_cms_page_block_' . $action, '/%gingerminds_core.admin_prefix%/pages/blocks/{key}/' . $action)
            ->controller('gingerminds_cms.controller.admin.block::' . $action)
            ->defaults(['resource' => 'page'])
            ->requirements(['key' => '[a-z0-9_]+'])
            ->methods(['POST']);
    }
};
