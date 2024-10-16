<?php

namespace Oro\Bundle\UserBundle\Provider;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\PlatformBundle\Provider\UsageStats\AbstractUsageStatsProvider;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Usage Stats provider for the number of users in the system
 */
class UsersUsageStatsProvider extends AbstractUsageStatsProvider
{
    private ObjectManager $objectManager;
    private OrganizationRestrictionProviderInterface $organizationRestrictionProvider;

    public function __construct(
        ObjectManager $objectManager,
        OrganizationRestrictionProviderInterface $organizationRestrictionProvider
    ) {
        $this->objectManager = $objectManager;
        $this->organizationRestrictionProvider = $organizationRestrictionProvider;
    }

    #[\Override]
    public function getTitle(): string
    {
        return 'oro.user.usage_stats.users.label';
    }

    #[\Override]
    public function getValue(): ?string
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->objectManager->getRepository(User::class);
        $queryBuilder = $userRepository->getUsersCountQueryBuilder();

        $this->organizationRestrictionProvider->applyOrganizationRestrictions(
            $queryBuilder
        );

        return (string)$queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }
}
