<?php

namespace Oro\Bundle\UserBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provider for email recipient list based on User.
 */
class EmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    private ManagerRegistry $doctrine;
    private EmailRecipientsHelper $emailRecipientsHelper;

    public function __construct(ManagerRegistry $doctrine, EmailRecipientsHelper $emailRecipientsHelper)
    {
        $this->doctrine = $doctrine;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(EmailRecipientsProviderArgs $args)
    {
        return $this->emailRecipientsHelper->getRecipients(
            $args,
            $this->doctrine->getRepository(User::class),
            'u',
            User::class
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSection(): string
    {
        return 'oro.user.entity_plural_label';
    }
}
