<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Updates email templates to new version matching old versions available for update by hashes
 */
class UpdateInviteUserEmailTemplates extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    #[\Override]
    protected function getEmailHashesToUpdate(): array
    {
        return [
            'invite_user' => [
                'c6c227715b6ffbfac9ad5bda0fcf933b', // 1.0.0.0
                'dd74f49c3083e85e287a729ee3e6d478', // 7.1.0.0
                '5f4d18936122644d7a98ec3d954e9e5a', // 7.1.0.1
                'f44604b4435c5119452f4890e816b344', // 7.1.0.2
            ]
        ];
    }

    #[\Override]
    public function getVersion(): string
    {
        return '7.1.0.2';
    }

    #[\Override]
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroUserBundle/Migrations/Data/ORM/invite_user_emails');
    }
}
