<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Updates email templates to new version matching old versions available for update by hashes
 */
class UpdateInviteUserEmailTemplates extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    protected function getEmailHashesToUpdate(): array
    {
        return [
            'invite_user' => [
                'c6c227715b6ffbfac9ad5bda0fcf933b', // 1.0.0.0
                'fce0c4e51e10159be7bb180579d48b17', // 6.0.10.0
                'dd74f49c3083e85e287a729ee3e6d478', // correctly recalculated hash of the 6.0.10.0 content
                'ebce3e31cae498eeebaadb6a6bcd6ef8', // 6.0.10.1
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        return '6.0.10.1';
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroUserBundle/Migrations/Data/ORM/invite_user_emails');
    }
}
