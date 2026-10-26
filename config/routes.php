<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Requirement\Requirement;

// Extra routes only: the CRUD routes come from the core `gingerminds_crud` loader.
return static function (RoutingConfigurator $routes): void {
    $routes->add('gingerminds_cms_menu_item_reorder', '/%gingerminds_core.admin_prefix%/menus/{menuId}/items/reorder')
        ->controller('%gingerminds_cms.resource.menu_item.controller%::reorder')
        ->requirements(['menuId' => Requirement::DIGITS])
        ->methods(['POST']);

    $routes->add('gingerminds_cms_page_category_reorder', '/%gingerminds_core.admin_prefix%/page-categories/reorder')
        ->controller('%gingerminds_cms.resource.page_category.controller%::reorder')
        ->methods(['POST']);

    // The block modal of the page content (BlockCanvasType).
    foreach (['form', 'validate'] as $action) {
        $routes->add('gingerminds_cms_page_block_' . $action, '/%gingerminds_core.admin_prefix%/pages/blocks/{key}/' . $action)
            ->controller('gingerminds_cms.controller.admin.block::' . $action)
            ->defaults(['resource' => 'page'])
            ->requirements(['key' => '[a-z0-9_]+'])
            ->methods(['POST']);
    }
};
