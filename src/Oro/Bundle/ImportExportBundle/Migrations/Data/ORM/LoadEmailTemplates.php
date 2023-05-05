<?php

namespace Oro\Bundle\ImportExportBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
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
        return '1.3';
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
            ->locateResource('@OroImportExportBundle/Migrations/Data/ORM/emails/importExport');
    }

    /**
     * {@inheritdoc}
     */
    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $template)
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

    /**
     * @return array|string
     */
    private function getPreviousEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroImportExportBundle/Migrations/Data/ORM/emails/v1_2');
    }
}
