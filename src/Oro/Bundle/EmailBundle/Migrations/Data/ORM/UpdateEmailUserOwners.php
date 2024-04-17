<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * The EmailUser entity must have the same organizational data as any other entity protected by an ACL. If the entity
 * does not have organization data, it will be lost and only the global organization will be able to see it.
 * Set the owner for all email users if they do not have one.
 */
class UpdateEmailUserOwners extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function load(ObjectManager $manager): void
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $managerRegistry = $this->container->get('doctrine');
        $defaultOwner = $managerRegistry->getRepository(User::class)->findOneBy([], ['id' => 'ASC']);

        $this->updateEmailUserOwners($defaultOwner);
    }

    private function updateEmailUserOwners(User $owner): void
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        $sql = <<<SQL
            UPDATE oro_email_user
            SET user_owner_id = :user_owner_id, organization_id = :organization_id
            WHERE user_owner_id IS NULL
        SQL;

        $connection->executeQuery(
            $sql,
            ['user_owner_id' => $owner->getId(), 'organization_id' => $owner->getOrganization()->getId()],
            ['user_owner_id' => Types::INTEGER, 'organization_id' => Types::INTEGER]
        );
    }
}
