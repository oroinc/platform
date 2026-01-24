<?php

namespace Oro\Bundle\EmailBundle\Autocomplete;

use Oro\Bundle\UserBundle\Autocomplete\OrganizationUsersHandler;

/**
 * Autocomplete search handler for mailbox users.
 *
 * Filters users by a specific organization and enabled status for mailbox assignment.
 */
class MailboxUserSearchHandler extends OrganizationUsersHandler
{
    protected $organizationId = null;

    /**
     * @param int $organizationId
     *
     * @return $this
     */
    public function setOrganizationId($organizationId)
    {
        $this->organizationId = $organizationId;

        return $this;
    }

    #[\Override]
    protected function getBasicQueryBuilder()
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('u');
        $queryBuilder->leftJoin('u.organizations', 'org')
            ->andWhere('org.id = :org')
            ->andWhere('u.enabled = :enabled')
            ->setParameter('org', $this->organizationId)
            ->setParameter('enabled', true);

        return $queryBuilder;
    }
}
