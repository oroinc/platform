<?php

namespace Oro\Bundle\UserBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;

class EmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    /** @var Registry */
    protected $registry;

    /** @var EmailRecipientsHelper */
    protected $emailRecipientsHelper;

    /**
     * @param Registry $registry
     * @param EmailRecipientsHelper $emailRecipientsHelper
     */
    public function __construct(
        Registry $registry,
        EmailRecipientsHelper $emailRecipientsHelper
    ) {
        $this->registry = $registry;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(EmailRecipientsProviderArgs $args)
    {
        $recipients = $this->emailRecipientsHelper->getRecipients(
            $args,
            $this->getUserRepository(),
            'u',
            'Oro\Bundle\UserBundle\Entity\User'
        );

        $result = [];
        foreach ($recipients as $email => $name) {
            $result[] = new Recipient($email, $name);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getSection()
    {
        return 'oro.user.entity_plural_label';
    }

    /**
     * @return UserRepository
     */
    protected function getUserRepository()
    {
        return $this->registry->getRepository('OroUserBundle:User');
    }
}
