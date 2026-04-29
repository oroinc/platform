<?php

namespace Oro\Bundle\IntegrationBundle\Api\Repository;

use Oro\Bundle\IntegrationBundle\Api\Model\WebhookFormat;
use Oro\Bundle\IntegrationBundle\Provider\WebhookFormatProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The repository to get available webhook formats.
 */
class WebhookFormatRepository
{
    public function __construct(
        private readonly WebhookFormatProvider $webhookFormatProvider,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * Returns all available webhook formats.
     *
     * @return WebhookFormat[]
     */
    public function getWebhookFormats(): array
    {
        $result = [];
        $formats = $this->webhookFormatProvider->getFormats();
        foreach ($formats as $key => $label) {
            $result[] = new WebhookFormat($key, $this->translator->trans($label));
        }

        return $result;
    }

    /**
     * Gets a webhook format by its key if it is one of the available webhook formats.
     */
    public function findWebhookFormat(string $key): ?WebhookFormat
    {
        $formats = $this->webhookFormatProvider->getFormats();
        if (!isset($formats[$key])) {
            return null;
        }

        return new WebhookFormat($key, $this->translator->trans($formats[$key]));
    }
}
