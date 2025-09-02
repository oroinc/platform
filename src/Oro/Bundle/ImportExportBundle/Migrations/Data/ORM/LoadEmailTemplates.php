<?php

namespace Oro\Bundle\ImportExportBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\EmailTemplateHydrator\EmailTemplateRawDataParser;
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
            ->locateResource('@OroImportExportBundle/Migrations/Data/ORM/emails/importExport');
    }

    #[\Override]
    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $arrayData): void
    {
        $oldTemplates = $this->getEmailTemplatesList($this->getPreviousEmailsDir());
        if (!isset($oldTemplates[$emailTemplate->getName()])) {
            return;
        }

        /** @var EmailTemplateRawDataParser $emailTemplateRawDataParser */
        $emailTemplateRawDataParser = $this->container->get('oro_email.email_template_hydrator.raw_data_parser');

        $oldRawData = file_get_contents($oldTemplates[$emailTemplate->getName()]['path']);
        $oldArrayData = $emailTemplateRawDataParser->parseRawData($oldRawData);

        if (md5($oldArrayData['content']) === md5($emailTemplate->getContent())) {
            parent::updateExistingTemplate($emailTemplate, $arrayData);
        }
    }

    private function getPreviousEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroImportExportBundle/Migrations/Data/ORM/emails/v1_2');
    }
}
