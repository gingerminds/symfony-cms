<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Menu;

use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\CoreBundle\Menu\AdminMenuProviderInterface;
use Gingerminds\CoreBundle\Menu\MenuItem;
use Gingerminds\CoreBundle\Resource\ResourceRegistry;
use Gingerminds\CoreBundle\Security\Voter\AbstractResourceVoter;

/**
 * "CMS" section of the admin menu, before the media manager one (50).
 */
final readonly class CmsAdminMenuProvider implements AdminMenuProviderInterface
{
    public const string SECTION = 'cms';

    private const array ENTRIES = [
        'page' => ['bi-file-earmark-text', 0],
        'page_category' => ['bi-diagram-3', 10],
        'menu' => ['bi-menu-button-wide', 100],
    ];

    public function __construct(
        private ResourceRegistry $resources,
    ) {
    }

    public function getItems(): iterable
    {
        $children = [];

        foreach (self::ENTRIES as $name => [$icon, $weight]) {
            if (!$this->resources->has($name)) {
                continue;
            }

            $resource = $this->resources->get($name);
            $children[] = new MenuItem(
                $resource->translationKey('name_p'),
                $resource->route('index'),
                icon: $icon,
                permission: AbstractResourceVoter::VIEW,
                permissionSubject: $name,
                translationDomain: $resource->translationDomain,
                weight: $weight,
            );
        }

        yield new MenuItem(
            'admin_menu.cms',
            icon: 'bi-layout-text-window-reverse',
            children: $children,
            translationDomain: GingermindsCmsBundle::TRANSLATION_DOMAIN,
            id: self::SECTION,
            weight: 40,
        );
    }
}
