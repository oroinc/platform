<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Loads email templates for grids.
 */
class LoadEmailTemplates extends AbstractEmailFixture implements VersionedFixtureInterface
{
    #[\Override]
    public function getVersion(): string
    {
        return '1.1';
    }

    #[\Override]
    protected function findExistingTemplate(ObjectManager $manager, array $template): ?EmailTemplate
    {
        if (empty($template['name'])) {
            return null;
        }

        return $manager->getRepository(EmailTemplate::class)->findOneBy([
            'name' => $template['name']
        ]);
    }

    #[\Override]
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroDataGridBundle/Migrations/Data/ORM/emails');
    }
}
