<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;

/**
 * Should be used for creating EmailTemplates migrations based on hash of old content of the template.
 * Logic is the next - if we found email template for certain name and same MD5 hash which is set in the migration array
 * this means we found template which should be replaced to the newest version.
 */
abstract class AbstractHashEmailMigration extends AbstractEmailFixture
{
    /**
     * {@inheritdoc}
     */
    protected function findExistingTemplate(ObjectManager $manager, array $template)
    {
        if (empty($template['params']['name'])) {
            return null;
        }

        return $manager->getRepository(EmailTemplate::class)->findOneBy([
            'name' => $template['params']['name'],
            'entityName' => $template['params']['entityName'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $template)
    {
        foreach ($this->getEmailHashesToUpdate() as $templateName => $contentHashes) {
            if ($emailTemplate->getName() === $templateName
                && ($contentHashes === true || \in_array(md5($emailTemplate->getContent()), $contentHashes, true))
            ) {
                parent::updateExistingTemplate($emailTemplate, $template);
            }
        }
    }

    /**
     * Specifies array of email template names with hashes to be updated to the newest version (which is set by
     * getEmailDir method)
     *
     * To update template without overriding customized content add it's name as key and add expected previous
     * content MD5 to array of hashes.
     * To force update replace content hashes array with true.
     *
     * [
     *     <template_name> => [<MD5_of_previous_version_allowed_to_update>],
     *     <template_name_2> => true
     * ]
     */
    abstract protected function getEmailHashesToUpdate(): array;
}
