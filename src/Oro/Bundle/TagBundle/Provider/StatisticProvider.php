<?php

namespace Oro\Bundle\TagBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\TagBundle\Security\SecurityProvider;

/**
 * Provides tag statistic.
 */
class StatisticProvider
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private SecurityProvider $securityProvider,
        private ConfigProvider $entityConfigProvider,
        private EntityAliasResolver $entityAliasResolver
    ) {
    }

    /**
     * @param Tag $tag
     *
     * @return array ['' => [count], $alias => [count, icon, label, class => true]]
     */
    public function getTagEntitiesStatistic(Tag $tag): array
    {
        $groupedResult = $this->getGroupedTagEntities($tag);

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
     * @param Tag $tag
     *
     * @return array [[cnt, entityClass]]
     */
    private function getGroupedTagEntities(Tag $tag)
    {
        $queryBuilder = $this->doctrine->getManagerForClass(Tagging::class)
            ->createQueryBuilder()
            ->select('COUNT(t.id) AS cnt, t.entityName AS entityClass')
            ->from(Tagging::class, 't')
            ->where('t.tag = :tag')
            ->setParameter('tag', $tag)
            ->addGroupBy('t.entityName');

        $this->securityProvider->applyAcl($queryBuilder, 't');

        return $queryBuilder->getQuery()->getResult();
    }
}
