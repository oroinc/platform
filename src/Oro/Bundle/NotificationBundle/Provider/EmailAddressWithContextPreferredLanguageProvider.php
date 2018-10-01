<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Oro\Bundle\LocaleBundle\Provider\BasePreferredLanguageProvider;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;

/**
 * Determines language for EmailAddressWithContext entity by initiating language determination process for the context.
 */
class EmailAddressWithContextPreferredLanguageProvider extends BasePreferredLanguageProvider
{
    /**
     * @var PreferredLanguageProviderInterface
     */
    private $chainLanguageProvider;

    /**
     * @param PreferredLanguageProviderInterface $chainLanguageProvider
     */
    public function __construct(PreferredLanguageProviderInterface $chainLanguageProvider)
    {
        $this->chainLanguageProvider = $chainLanguageProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof EmailAddressWithContext;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreferredLanguageForEntity($entity): string
    {
        return $this->chainLanguageProvider->getPreferredLanguage($entity->getContext());
    }
}
