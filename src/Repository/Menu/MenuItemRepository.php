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
     * The children of a deleted item move to the root (as `parent_id` ON DELETE SET NULL),
     * whatever the database.
     */
    protected function beforeRemove(object $entity): void
    {
        if ($entity instanceof MenuItemInterface) {
            foreach ($entity->getChildren() as $child) {
                $child->setParent(null);
            }
        }
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
