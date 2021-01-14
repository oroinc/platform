<?php

namespace Oro\Bundle\ImportExportBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;
use Oro\Bundle\MigrationBundle\Fixture\RenamedFixtureInterface;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Loads email templates to the database.
 */
class LoadEmailTemplates extends AbstractEmailFixture implements
    VersionedFixtureInterface,
    RenamedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.2';
    }

    /**
     * {@inheritDoc}
     */
    public function getPreviousClassNames(): array
    {
        return [
            'Oro\\Bundle\\ImportExport\\Migrations\\Data\\ORM\\LoadEmailTemplates',
        ];
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
