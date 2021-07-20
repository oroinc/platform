<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;

class ContextEmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

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

        return EmailRecipientsHelper::filterRecipients(
            $args,
            $this->relatedEmailsProvider->getRecipients($args->getRelatedEntity(), 2, false, $args->getOrganization())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSection()
    {
        return 'oro.email.autocomplete.contexts';
    }
}
