<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Repository\Menu;

use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CmsBundle\Entity\Menu\BaseMenu;
use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\CmsBundle\Entity\Menu\MenuItemInterface;
use Gingerminds\CoreBundle\Repository\AbstractRepository;
use Symfony\Component\Form\FormInterface;

/**
 * @extends AbstractRepository<MenuItemInterface>
 */
class MenuItemRepository extends AbstractRepository
{
    /**
     * @param class-string<MenuItemInterface> $entityClass
     */
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * Every item of a menu with its translations, ordered, by a single query.
     *
     * @return list<MenuItemInterface>
     */
    public function findByMenu(MenuInterface $menu): array
    {
        /** @var list<MenuItemInterface> $items */
        $items = $this->createQueryBuilder('i')
            ->addSelect('t', 'l')
            ->leftJoin('i.translations', 't')
            ->leftJoin('t.language', 'l')
            ->andWhere('i.menu = :menu')
            ->setParameter('menu', $menu->getId())
            ->getQuery()
            ->getResult();

        return BaseMenu::sortItems($items);
    }

    /**
     * Applies the order of one level of a menu tree: ids of other levels or menus are ignored.
     *
     * @param list<int> $ids
     */
    public function reorder(MenuInterface $menu, ?int $parentId, array $ids): void
    {
        $siblings = [];

        foreach ($this->findBy(['menu' => $menu, 'parent' => $parentId]) as $item) {
            $siblings[(int) $item->getId()] = $item;
        }

        foreach (array_values($ids) as $position => $id) {
            if (isset($siblings[$id])) {
                $siblings[$id]->setPosition($position);
            }
        }

        $this->getEntityManager()->flush();
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
     * A new item, or one moved to another parent, goes to the end of its level.
     */
    protected function beforeSave(object $entity, ?FormInterface $form): void
    {
        if (!$entity instanceof MenuItemInterface) {
            return;
        }

        $original = $this->getEntityManager()->getUnitOfWork()->getOriginalEntityData($entity);

        if ([] !== $original && ($original['parent'] ?? null) === $entity->getParent()) {
            return;
        }

        $qb = $this->createQueryBuilder('i')
            ->select('MAX(i.position)')
            ->andWhere('i.menu = :menu')
            ->setParameter('menu', $entity->getMenu()?->getId());
        $parent = $entity->getParent();

        if (!$parent instanceof MenuItemInterface) {
            $qb->andWhere('i.parent IS NULL');
        } else {
            $qb->andWhere('i.parent = :parent')->setParameter('parent', $parent->getId());
        }

        if (null !== $entity->getId()) {
            $qb->andWhere('i.id <> :id')->setParameter('id', $entity->getId());
        }

        $max = $qb->getQuery()->getSingleScalarResult();
        $entity->setPosition(null === $max ? 0 : (int) $max + 1);
    }
}
