<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

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
        $qb->add(
            'select',
            new Expr\Select(
                array(
                    'ni.id',
                    'ni.url',
                    'ni.title',
                )
            )
        )
            ->add('from', new Expr\From($this->_entityName, 'ni'))
            ->add(
                'where',
                $qb->expr()->andx(
                    $qb->expr()->eq('ni.user', ':user'),
                    $qb->expr()->eq('ni.organization', ':organization')
                )
            )
            ->setParameters(array('user' => $user, 'organization' => $organization));

        $orderBy = array(array('field' => NavigationHistoryItem::NAVIGATION_HISTORY_COLUMN_VISITED_AT));
        if (isset($options['order_by'])) {
            $orderBy = (array) $options['order_by'];
        }
        $fields = $this->_em->getClassMetadata($this->_entityName)->getFieldNames();
        foreach ($orderBy as $order) {
            if (isset($order['field']) && in_array($order['field'], $fields)) {
                $qb->addOrderBy(
                    'ni.' . $order['field'],
                    isset($order['dir']) ? $order['dir'] : self::DEFAULT_SORT_ORDER
                );
            }
        }
        if (isset($options['max_items'])) {
            $qb->setMaxResults((int) $options['max_items']);
        }

        return $qb->getQuery()->getArrayResult();
    }
}
