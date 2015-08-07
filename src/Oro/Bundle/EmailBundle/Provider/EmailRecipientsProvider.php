<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class EmailRecipientsProvider
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var EmailRecipientsProviderInterface[] */
    protected $providers = [];

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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
        $excludeEmails = [];
        foreach ($this->providers as $provider) {
            if ($limit <= 0) {
                break;
            }

            $args = new EmailRecipientsProviderArgs($relatedEntity, $query, $limit, $excludeEmails, $organization);
            $recipients = $provider->getRecipients($args);
            if (!$recipients) {
                continue;
            }

            $recipientEmails = array_map(function (Recipient $recipient) {
                return $recipient->getEmail();
            }, $recipients);
            $excludeEmails = array_merge($excludeEmails, $recipientEmails);
            $limit = max([0, $limit - count($recipients)]);
            if (!array_key_exists($provider->getSection(), $emails)) {
                $emails[$provider->getSection()] = [];
            }
            $emails[$provider->getSection()] = array_merge($emails[$provider->getSection()], $recipients);
        }

        $result = [];
        foreach ($emails as $section => $sectionEmails) {
            $items = array_map(function (Recipient $recipient) {
                $id = ['key' => $recipient->getName()];
                if ($recipientEntity = $recipient->getEntity()) {
                    $id['contextText'] = $recipient->getEntity()->getLabel();
                    $id['contextValue'] = [
                        'entityClass' => $recipient->getEntity()->getClass(),
                        'entityId' => $recipient->getEntity()->getId(),
                    ];
                    $id['organization'] = $recipient->getEntity()->getOrganization();
                }

                return [
                    'id' => [
                        json_encode($id),
                    ],
                    'text' => $recipient->getLabel(),
                ];
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
