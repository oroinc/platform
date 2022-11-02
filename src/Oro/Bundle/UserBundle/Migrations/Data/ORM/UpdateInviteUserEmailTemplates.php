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
     * {@inheritdoc}
     */
    protected function getEmailHashesToUpdate(): array
    {
        return [
            'invite_user' => ['c6c227715b6ffbfac9ad5bda0fcf933b']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroUserBundle/Migrations/Data/ORM/invite_user_emails');
    }
}
