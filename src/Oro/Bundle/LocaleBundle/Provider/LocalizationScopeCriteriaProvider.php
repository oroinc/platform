<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;

/**
 * The scope criteria provider for the current localization.
 */
class LocalizationScopeCriteriaProvider implements ScopeCriteriaProviderInterface
{
    public const LOCALIZATION = 'localization';

    /** @var CurrentLocalizationProvider */
    private $currentLocalizationProvider;

    public function __construct(CurrentLocalizationProvider $currentLocalizationProvider)
    {
        $this->currentLocalizationProvider = $currentLocalizationProvider;
    }

    #[\Override]
    public function getCriteriaField()
    {
        return self::LOCALIZATION;
    }

    #[\Override]
    public function getCriteriaValue()
    {
        return $this->currentLocalizationProvider->getCurrentLocalization();
    }

    #[\Override]
    public function getCriteriaValueType()
    {
        return Localization::class;
    }
}
