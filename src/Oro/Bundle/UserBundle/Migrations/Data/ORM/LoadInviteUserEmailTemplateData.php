<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Loads invite_user_emails email template which was converted from a twig template file.
 */
class LoadInviteUserEmailTemplateData extends AbstractEmailFixture
{
    /**
     * {@inheritDoc}
     */
    protected function findExistingTemplate(ObjectManager $manager, array $template): ?EmailTemplate
    {
        if (empty($template['params']['name'])) {
            return null;
        }

        return $manager->getRepository(EmailTemplate::class)->findOneBy([
            'name' => $template['params']['name'],
            'entityName' => User::class
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $template): void
    {
        // Skip if such template exists
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroUserBundle/Migrations/Data/ORM/invite_user_emails');
    }
}
