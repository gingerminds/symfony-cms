<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Security\Voter\MenuVoter;

/*
 * Voters.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.security.voter.menu', MenuVoter::class)
        ->tag('security.voter');
};
