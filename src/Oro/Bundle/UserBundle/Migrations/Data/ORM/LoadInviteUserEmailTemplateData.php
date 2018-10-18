<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Loads invite_user_emails email template which was converted from a twig template file.
 */
class LoadInviteUserEmailTemplateData extends AbstractEmailFixture
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
            'entityName' => User::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $template)
    {
        // Skip if such template exists
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
