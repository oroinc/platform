<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * PinbarTab Repository
 */
class PinbarTabRepository extends EntityRepository implements NavigationRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems($user, Organization $organization, $type = null, $options = array())
    {
        $qb = $this->createNavigationItemsQueryBuiler($user, $organization, $type);
        $qb
            ->addSelect('pt.title as title_rendered', 'pt.titleShort as title_rendered_short')
            ->orderBy('ni.position', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Increment positions of Pinbar tabs for specified user
     *
     * @param User|integer $user
     * @param int          $navigationItemId
     * @param Organization $organization
     *
     * @return mixed
     */
    public function incrementTabsPositions($user, $navigationItemId, Organization $organization)
    {
        $qb = $this->_em->createQueryBuilder();

        return $qb->update($this->getNavigationItemClassName(), 'p')
            ->set('p.position', 'p.position + 1')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->neq('p.id', ':item_id'),
                    $qb->expr()->eq('p.type', ':type'),
                    $qb->expr()->eq('p.user', ':user'),
                    $qb->expr()->eq('p.organization', ':organization')
                )
            )
            ->setParameter('item_id', $navigationItemId, \PDO::PARAM_INT)
            ->setParameter('type', 'frontend_pinbar', \PDO::PARAM_STR)
            ->setParameter('user', $user)
            ->setParameter('organization', $organization)
            ->getQuery()
            ->execute();
    }

    /**
     * @return string
     */
    protected function getNavigationItemClassName()
    {
        return 'Oro\Bundle\NavigationBundle\Entity\NavigationItem';
    }

    /**
     * @param string $url
     * @param AbstractUser|integer $user
     * @param OrganizationInterface|null $organization
     * @param string|null $type
     *
     * @return integer
     */
    public function countNavigationItems(
        string $url,
        $user,
        OrganizationInterface $organization = null,
        $type = null
    ): int {
        $qb = $this->createNavigationItemsQueryBuiler($user, $organization, $type);

        $qb
            ->resetDQLPart('select')
            ->select('count(pt.id)')
            ->andWhere($qb->expr()->eq('ni.url', ':url'))
            ->setParameter('url', $url);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param AbstractUser|integer $user
     * @param OrganizationInterface $organization|null
     * @param string|null $type
     *
     * @return QueryBuilder
     */
    private function createNavigationItemsQueryBuiler(
        $user,
        OrganizationInterface $organization = null,
        string $type = null
    ): QueryBuilder {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select(
                'pt.id',
                'ni.url',
                'ni.title',
                'ni.type',
                'ni.id AS parent_id',
                'pt.maximized'
            )
            ->from($this->_entityName, 'pt')
            ->innerJoin('pt.item', 'ni', Expr\Join::WITH)
            ->andWhere($qb->expr()->eq('ni.user', ':user'))
            ->setParameter('user', $user)
            ->andWhere($qb->expr()->eq('ni.type', ':type'))
            ->setParameter('type', $type);
        if ($organization === null) {
            $qb->andWhere($qb->expr()->isNull('ni.organization'));
        } else {
            $qb->andWhere($qb->expr()->eq('ni.organization', ':organization'))
                ->setParameter('organization', $organization);
        }

        return $qb;
    }

    /**
     * @param string $titleShort
     * @param AbstractUser|integer $user
     * @param OrganizationInterface|null $organization
     *
     * @return int
     */
    public function countPinbarTabDuplicatedTitles(
        string $titleShort,
        $user,
        OrganizationInterface $organization = null
    ): int {
        $qb = $this->createNavigationItemsQueryBuiler($user, $organization, 'pinbar');

        $qb
            ->resetDQLPart('select')
            ->select('count(pt.id)')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('pt.titleShort', ':title_short'),
                    $qb->expr()->like('pt.titleShort', ':title_short_like')
                )
            )
            ->setParameter('title_short', $titleShort)
            ->setParameter('title_short_like', $titleShort.' (%');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
