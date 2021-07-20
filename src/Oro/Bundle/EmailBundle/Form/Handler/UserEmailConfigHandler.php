<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;

/**
 * Saves user imap configuration settings by handling 'user_email_configuration' processing.
 */
class UserEmailConfigHandler
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Save user with new configuration
     */
    public function handle(ConfigManager $manager, ConfigChangeSet $changeSet, FormInterface $form)
    {
        $configKey = Configuration::getConfigKeyByName('user_mailbox', ConfigManager::SECTION_VIEW_SEPARATOR);
        if (!$form->has($configKey)) {
            return;
        }
        $mailboxChildForm = $form->get($configKey);
        $formData = $mailboxChildForm->getData();
        $user = is_array($formData) && isset($formData['value']) ? $formData['value'] : null;
        if (!$user instanceof User) {
            return;
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
