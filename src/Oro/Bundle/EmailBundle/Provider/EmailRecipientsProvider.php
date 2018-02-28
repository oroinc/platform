<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Translation\TranslatorInterface;

class EmailRecipientsProvider
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var EmailRecipientsHelper */
    protected $emailRecipientsHelper;

    /** @var EmailRecipientsProviderInterface[] */
    protected $providers = [];

    /**
     * @param TranslatorInterface $translator
     * @param EmailRecipientsHelper $emailRecipientsHelper
     */
    public function __construct(TranslatorInterface $translator, EmailRecipientsHelper $emailRecipientsHelper)
    {
        $this->translator = $translator;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
    }

    /**
     * @param object|null $relatedEntity
     * @param string|null $query
     * @param Organization|null $organization
     * @param int $limit
     *
     * @return array
     */
    public function getEmailRecipients(
        $relatedEntity = null,
        $query = null,
        Organization $organization = null,
        $limit = 100
    ) {
        $emails = [];
        foreach ($this->providers as $provider) {
            if ($limit <= 0) {
                break;
            }

            $args = new EmailRecipientsProviderArgs(
                $relatedEntity,
                $query,
                $limit,
                array_reduce($emails, 'array_merge', []),
                $organization
            );
            $recipients = $provider->getRecipients($args);
            if (!$recipients) {
                continue;
            }

            $limit = max([0, $limit - count($recipients)]);
            if (!array_key_exists($provider->getSection(), $emails)) {
                $emails[$provider->getSection()] = [];
            }
            $emails[$provider->getSection()] = array_merge($emails[$provider->getSection()], $recipients);
        }

        $result = [];
        foreach ($emails as $section => $sectionEmails) {
            $items = array_map(function (Recipient $recipient) {
                return $this->emailRecipientsHelper->createRecipientData($recipient);
            }, $sectionEmails);

            $result[] = [
                'text'     => $this->translator->trans($section),
                'children' => array_values($items),
            ];
        }

        return $result;
    }

    /**
     * @param EmailRecipientsProviderInterface[] $providers
     */
    public function setProviders(array $providers)
    {
        $this->providers = $providers;
    }
}
