<?php

namespace Oro\Bundle\TagBundle\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Security\SecurityProvider;

class StatisticProvider
{
    /** @var EntityManager */
    protected $em;

    /** @var SecurityProvider */
    protected $securityProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param EntityManager       $entityManager
     * @param SecurityProvider    $securityProvider
     * @param ConfigProvider      $configProvider
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(
        EntityManager $entityManager,
        SecurityProvider $securityProvider,
        ConfigProvider $configProvider,
        EntityAliasResolver $entityAliasResolver
    ) {
        $this->em                   = $entityManager;
        $this->securityProvider     = $securityProvider;
        $this->entityConfigProvider = $configProvider;
        $this->entityAliasResolver  = $entityAliasResolver;
    }

    /**
     * @param Tag $tag
     *
     * @return array ['' => [count], $alias => [count, icon, label, class => true]]
     */
    public function getTagEntitiesStatistic(Tag $tag)
    {
        $groupedResult = $this->getGroupedTagEntities($tag);

        return array_reduce(
            $groupedResult,
            function ($result, array $entityResult) {
                $result['']['count'] += $entityResult['cnt'];
                $entityClass    = $entityResult['entityClass'];
                $alias          = $this->entityAliasResolver->getAlias($entityClass);
                $result[$alias] = [
                    'count' => $entityResult['cnt'],
                    'icon'  => $this->entityConfigProvider->getConfig($entityClass)->get('icon'),
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
    protected function getGroupedTagEntities(Tag $tag)
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('COUNT(t.id) AS cnt, t.entityName AS entityClass')
            ->from('Oro\Bundle\TagBundle\Entity\Tagging', 't')
            ->where('t.tag = :tag')
            ->setParameter('tag', $tag)
            ->addGroupBy('t.entityName');

        $this->securityProvider->applyAcl($queryBuilder, 't');

        return $queryBuilder->getQuery()->getResult();
    }
}
