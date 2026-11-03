<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Repository\Menu;

use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CmsBundle\Entity\Menu\MenuItemInterface;
use Gingerminds\CoreBundle\Model\TreeNodeInterface;
use Gingerminds\CoreBundle\Repository\AbstractRepository;
use Gingerminds\CoreBundle\Repository\TreeRepositoryInterface;
use Gingerminds\CoreBundle\Repository\TreeRepositoryTrait;

/**
 * @extends AbstractRepository<MenuItemInterface>
 */
class MenuItemRepository extends AbstractRepository implements TreeRepositoryInterface
{
    use TreeRepositoryTrait;

    /**
     * @param class-string<MenuItemInterface> $entityClass
     */
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * One tree per menu.
     *
     * @return array<string, mixed>
     */
    protected function treeScope(TreeNodeInterface $node): array
    {
        return $node instanceof MenuItemInterface ? ['menu' => $node->getMenu()] : [];
    }
}
