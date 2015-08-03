<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;

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

        return $this->relatedEmailsProvider->getEmails($args->getRelatedEntity(), 2);
    }

    /**
     * {@inheritdoc}
     */
    public function getSection()
    {
        return 'oro.email.autocomplete.contexts';
    }
}
