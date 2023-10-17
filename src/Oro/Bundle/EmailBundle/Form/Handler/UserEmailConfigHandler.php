<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;

/**
 * Saves user imap configuration settings by handling 'user_email_configuration' processing.
 */
class UserEmailConfigHandler
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function handle(ConfigManager $manager, ConfigChangeSet $changeSet, FormInterface $form): void
    {
        $formFieldName = 'oro_email' . ConfigManager::SECTION_VIEW_SEPARATOR . 'user_mailbox';
        if (!$form->has($formFieldName)) {
            return;
        }

        $formData = $form->get($formFieldName)->getData();
        $user = \is_array($formData) && isset($formData[ConfigManager::VALUE_KEY])
            ? $formData[ConfigManager::VALUE_KEY]
            : null;
        if (!$user instanceof User) {
            return;
        }

        $em = $this->doctrine->getManagerForClass(User::class);
        $em->persist($user);
        $em->flush();
    }
}
