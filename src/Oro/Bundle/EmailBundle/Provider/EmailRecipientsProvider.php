<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface;

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
     * @param int $limit
     *
     * @return array
     */
    public function getEmailRecipients($relatedEntity = null, $query = null, $limit = 100)
    {
        $emails = [];
        $excludeEmails = [];
        foreach ($this->providers as $provider) {
            if ($limit <= 0) {
                break;
            }

            $args = new EmailRecipientsProviderArgs($relatedEntity, $query, $limit, $excludeEmails);
            $recipients = $provider->getRecipients($args);
            if (!$recipients) {
                continue;
            }

            $excludeEmails = array_merge($excludeEmails, array_keys($recipients));
            $limit = max([0, $limit - count($recipients)]);
            if (!array_key_exists($provider->getSection(), $emails)) {
                $emails[$provider->getSection()] = [];
            }
            $emails[$provider->getSection()] = array_merge($emails[$provider->getSection()], $recipients);
        }

        $result = [];
        foreach ($emails as $section => $sectionEmails) {
            $items = array_map(function ($name) {
                return ['id' => $name, 'text' => $name];
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
