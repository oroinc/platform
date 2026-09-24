<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Updates email templates to new version matching old versions available for update by hashes
 */
class UpdateEmailTemplates extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    protected function getEmailHashesToUpdate(): array
    {
        return [
            'user_change_password' => ['55cf4f5b78600eabeb3d14ea0d4aa5ae'],
            'force_reset_password' => [
                '94bf65b402a50c53b3bef0f88c2cf121', // 1.0
                '48308cf739b6771294e3098e261c6aa7', // 6.0.10.1
                '8b20e309a90df86959f66f7b2016cacb', // 6.0.12.0
            ],
            'user_reset_password' => [
                '1e707aadaa5524244233001c2850c6f8', // 1.0
                '1128cb4636b0582e72c20bc7862e0ea5', // 6.0.12.0
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        return '6.0.12.0';
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroUserBundle/Migrations/Data/ORM/emails/user');
    }
}
