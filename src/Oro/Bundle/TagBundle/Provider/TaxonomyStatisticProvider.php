<?php

namespace Oro\Bundle\TagBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Taxonomy;
use Oro\Bundle\TagBundle\Security\SecurityProvider;

/**
 * Provides taxonomy statistic.
 */
class TaxonomyStatisticProvider
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private SecurityProvider $securityProvider,
        private ConfigProvider $entityConfigProvider,
        private EntityAliasResolver $entityAliasResolver
    ) {
    }

    /**
     * @param Taxonomy $taxonomy
     *
     * @return array ['' => [count], $alias => [count, icon, label, class => true]]
     */
    public function getTagEntitiesStatistic(Taxonomy $taxonomy): array
    {
        $groupedResult = $this->getGroupedTaxonomyEntities($taxonomy);

        return array_reduce(
            $groupedResult,
            function ($result, array $entityResult) {
                $result['']['count'] += $entityResult['cnt'];
                $entityClass = $entityResult['entityClass'];
                $alias = $this->entityAliasResolver->getAlias($entityClass);
                $result[$alias] = [
                    'count' => $entityResult['cnt'],
                    'icon' => $this->entityConfigProvider->getConfig($entityClass)->get('icon'),
                    'label' => $this->entityConfigProvider->getConfig($entityClass)->get('plural_label'),
                    'class' => true
                ];

                return $result;
            },
            ['' => ['count' => 0]]
        );
    }

    /**
     * @param Taxonomy $taxonomy
     *
     * @return array [[cnt, entityClass]]
     */
    private function getGroupedTaxonomyEntities(Taxonomy $taxonomy): array
    {
        $queryBuilder = $this->doctrine->getManagerForClass(Tag::class)
            ->createQueryBuilder()
            ->select('COUNT(t.id) AS cnt, t.taxonomy')
            ->from(Tag::class, 't')
            ->where('t.taxonomy = :taxonomy')
            ->setParameter('taxonomy', $taxonomy)
            ->addGroupBy('t.taxonomy');

        $this->securityProvider->applyAcl($queryBuilder, 't');

        return $queryBuilder->getQuery()->getResult();
    }
}
