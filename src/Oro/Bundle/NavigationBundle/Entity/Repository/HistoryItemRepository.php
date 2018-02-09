<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * NavigationItem Repository
 */
class HistoryItemRepository extends EntityRepository implements NavigationRepositoryInterface
{
    const DEFAULT_SORT_ORDER = 'DESC';

    /**
     * {@inheritdoc}
     */
    public function getNavigationItems($user, Organization $organization, $type = null, $options = array())
    {
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select(
                'ni.id',
                'ni.url',
                'ni.title',
                'ni.route'
            )
            ->from($this->_entityName, 'ni')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('ni.user', ':user'),
                    $qb->expr()->eq('ni.organization', ':organization')
                )
            )
            ->setParameters(['user' => $user, 'organization' => $organization]);

        $orderBy = [['field' => NavigationHistoryItem::NAVIGATION_HISTORY_COLUMN_VISITED_AT]];
        if (isset($options['order_by'])) {
            $orderBy = (array)$options['order_by'];
        }
        $fields = $this->_em->getClassMetadata($this->_entityName)->getFieldNames();
        foreach ($orderBy as $order) {
            if (isset($order['field']) && \in_array($order['field'], $fields, true)) {
                $qb->addOrderBy(
                    QueryBuilderUtil::getField('ni', $order['field']),
                    QueryBuilderUtil::getSortOrder($order['dir'] ?? self::DEFAULT_SORT_ORDER)
                );
            }
        }
        if (isset($options['max_items'])) {
            $qb->setMaxResults((int)$options['max_items']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function clearHistoryItems(\DateTime $dateTime)
    {
        $qb = $this->createQueryBuilder('navigation_history')
            ->delete()
            ->where('navigation_history.visitedAt < :dateTime')
            ->setParameter('dateTime', $dateTime);

        return $qb->getQuery()
            ->execute();
    }
}
