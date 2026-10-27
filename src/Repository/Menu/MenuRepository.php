<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Repository\Menu;

use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\CoreBundle\Repository\AbstractRepository;
use Gingerminds\MultisiteBundle\Doctrine\Filter\SiteFilter;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

/**
 * @extends AbstractRepository<MenuInterface>
 */
class MenuRepository extends AbstractRepository
{
    /**
     * @param class-string<MenuInterface> $entityClass
     */
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * The menus of a site, whatever the current site of the request.
     *
     * @return list<MenuInterface>
     */
    public function findBySite(SiteInterface $site): array
    {
        $filters = $this->getEntityManager()->getFilters();
        $suspended = $filters->isEnabled(SiteFilter::NAME);

        if ($suspended) {
            $filters->suspend(SiteFilter::NAME);
        }

        try {
            /** @var list<MenuInterface> */
            return $this->createQueryBuilder(self::ALIAS)
                ->andWhere(self::ALIAS . '.site = :site')
                ->setParameter('site', $site->getId())
                ->orderBy(self::ALIAS . '.code', 'ASC')
                ->getQuery()
                ->getResult();
        } finally {
            if ($suspended) {
                $filters->restore(SiteFilter::NAME);
            }
        }
    }
}
