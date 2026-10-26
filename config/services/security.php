<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Security\Voter\MenuItemVoter;
use Gingerminds\CmsBundle\Security\Voter\MenuVoter;
use Gingerminds\CmsBundle\Security\Voter\PageCategoryVoter;
use Gingerminds\CmsBundle\Security\Voter\PageVoter;

/*
 * Voters.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.security.voter.menu', MenuVoter::class)
        ->tag('security.voter');
    $services->set('gingerminds_cms.security.voter.menu_item', MenuItemVoter::class)
        ->tag('security.voter');
    $services->set('gingerminds_cms.security.voter.page_category', PageCategoryVoter::class)
        ->tag('security.voter');
    $services->set('gingerminds_cms.security.voter.page', PageVoter::class)
        ->tag('security.voter');
};
