<?php

namespace Oro\Bundle\ImportExportBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;
use Oro\Bundle\MigrationBundle\Fixture\RenamedFixtureInterface;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Loads email templates.
 */
class LoadEmailTemplates extends AbstractEmailFixture implements
    VersionedFixtureInterface,
    RenamedFixtureInterface
{
    #[\Override]
    public function getVersion(): string
    {
        return '1.3';
    }

    #[\Override]
    public function getPreviousClassNames(): array
    {
        return [
            'Oro\\Bundle\\ImportExport\\Migrations\\Data\\ORM\\LoadEmailTemplates',
        ];
    }

    #[\Override]
    protected function findExistingTemplate(ObjectManager $manager, array $template): ?EmailTemplate
    {
        if (empty($template['params']['name'])) {
            return null;
        }

        return $manager->getRepository(EmailTemplate::class)->findOneBy([
            'name' => $template['params']['name']
        ]);
    }

    #[\Override]
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroImportExportBundle/Migrations/Data/ORM/emails/importExport');
    }

    #[\Override]
    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $template): void
    {
        $oldTemplates = $this->getEmailTemplatesList($this->getPreviousEmailsDir());
        if (!isset($oldTemplates[$emailTemplate->getName()])) {
            return;
        }

        $oldTemplate = file_get_contents($oldTemplates[$emailTemplate->getName()]['path']);
        $oldTemplate = EmailTemplate::parseContent($oldTemplate);

        if (md5($oldTemplate['content']) === md5($emailTemplate->getContent())) {
            parent::updateExistingTemplate($emailTemplate, $template);
        }
    }

    private function getPreviousEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroImportExportBundle/Migrations/Data/ORM/emails/v1_2');
    }
}
