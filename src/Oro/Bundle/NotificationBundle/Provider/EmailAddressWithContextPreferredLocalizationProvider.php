<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\AbstractPreferredLocalizationProvider;
use Oro\Bundle\LocaleBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;

/**
 * Determines localization for EmailAddressWithContext entity
 * by initiating localization determination process for the context
 */
class EmailAddressWithContextPreferredLocalizationProvider extends AbstractPreferredLocalizationProvider
{
    /** @var PreferredLocalizationProviderInterface */
    private $innerLocalizationProvider;

    public function __construct(PreferredLocalizationProviderInterface $innerLocalizationProvider)
    {
        $this->innerLocalizationProvider = $innerLocalizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof EmailAddressWithContext;
    }

    /**
     * @param EmailAddressWithContext $entity
     * @return Localization|null
     */
    public function getPreferredLocalizationForEntity($entity): ?Localization
    {
        return $this->innerLocalizationProvider->getPreferredLocalization($entity->getContext());
    }
}
