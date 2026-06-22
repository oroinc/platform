<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Loads email templates for UserBundle
 */
class LoadEmailTemplates extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    /**
     * @inhericDoc
     */
    protected function getEmailHashesToUpdate(): array
    {
        return [
            'user_change_password' => [
                '52aabb5b751650ff2c638bac3a6bc66d', // 1.1
                '55cf4f5b78600eabeb3d14ea0d4aa5ae', // 5.0.23.0
            ],
            'force_reset_password' => [
                '8c6982893916d448afe4b083f9a27390', // 1.1
                '94bf65b402a50c53b3bef0f88c2cf121', // 5.0.23.0
            ],
            'user_impersonate' => [
                'ead06ae704e4c05d1933eb0d5434b8d1', // 1.1
            ],
            'user_reset_password' => [
                '0c6cc530f1f90186450878a3176f3c20', // 1.1
                '1e707aadaa5524244233001c2850c6f8', // 5.0.23.0
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '5.0.23.0';
    }

    /**
     * {@inheritdoc}
     */
    protected function findExistingTemplate(ObjectManager $manager, array $template)
    {
        if (empty($template['params']['name'])) {
            return null;
        }

        return $manager->getRepository('OroEmailBundle:EmailTemplate')->findOneBy([
            'name' => $template['params']['name'],
            'entityName' => 'Oro\Bundle\UserBundle\Entity\User',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroUserBundle/Migrations/Data/ORM/emails');
    }
}
