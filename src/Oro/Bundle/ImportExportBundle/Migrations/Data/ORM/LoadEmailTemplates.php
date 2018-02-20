<?php

namespace Oro\Bundle\ImportExport\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

class LoadEmailTemplates extends AbstractEmailFixture implements VersionedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.2';
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
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroImportExportBundle/Migrations/Data/ORM/emails');
    }
}
