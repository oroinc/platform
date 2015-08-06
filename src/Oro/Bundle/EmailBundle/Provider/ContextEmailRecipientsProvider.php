<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;

class ContextEmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /**
     * @param RelatedEmailsProvider $relatedEmailsProvider
     */
    public function __construct(RelatedEmailsProvider $relatedEmailsProvider)
    {
        $this->relatedEmailsProvider = $relatedEmailsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(EmailRecipientsProviderArgs $args)
    {
        if (!$args->getRelatedEntity()) {
            return [];
        }

        $recipients = EmailRecipientsHelper::filterRecipients(
            $args,
            $this->relatedEmailsProvider->getEmails($args->getRelatedEntity(), 2)
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
        return 'oro.email.autocomplete.contexts';
    }
}
