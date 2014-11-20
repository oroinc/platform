<?php

namespace Oro\Bundle\CalendarBundle\Autocomplete;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Autocomplete\UserAclHandler;
use Oro\Bundle\UserBundle\Entity\User;

class UserCalendarHandler extends UserAclHandler
{
    /**
     * @param EntityManager     $em
     * @param AttachmentManager $attachmentManager
     * @param string            $className
     * @param ServiceLink       $securityContextLink
     * @param OwnerTreeProvider $treeProvider
     * @param AclHelper         $aclHelper
     * @param AclVoter          $aclVoter
     */
    public function __construct(
        EntityManager $em,
        AttachmentManager $attachmentManager,
        $className,
        ServiceLink $securityContextLink,
        OwnerTreeProvider $treeProvider,
        AclHelper $aclHelper,
        AclVoter $aclVoter = null
    ) {
        parent::__construct($em, $attachmentManager, $className, $securityContextLink, $treeProvider, $aclVoter);

        $this->aclHelper           = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchQueryBuilder($search)
    {
        $qb = parent::getSearchQueryBuilder($search);

        $qb
            ->select('calendar')
            ->innerJoin('OroCalendarBundle:Calendar', 'calendar', 'WITH', 'calendar.owner = users');

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function addAcl(QueryBuilder $queryBuilder, $accessLevel, User $user, Organization $organization)
    {
        $queryBuilder->andWhere($queryBuilder->expr()->eq('calendar.organization', $organization->getId()));
        $this->aclHelper->apply($queryBuilder);
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($calendar)
    {
        $result = parent::convertItem($calendar->getOwner());
        $result['id'] = $calendar->getId();

        return $result;
    }
}
