<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider;

class LocalizationScopeCriteriaProvider extends AbstractScopeCriteriaProvider
{
    const LOCALIZATION = 'localization';

    /**
     * @var CurrentLocalizationProvider
     */
    protected $currentLocalizationProvider;

    /**
     * @param CurrentLocalizationProvider $currentLocalizationProvider
     */
    public function __construct(CurrentLocalizationProvider $currentLocalizationProvider)
    {
        $this->currentLocalizationProvider = $currentLocalizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaForCurrentScope()
    {
        return [static::LOCALIZATION => $this->currentLocalizationProvider->getCurrentLocalization()];
    }

    /**
     * @return string
     */
    public function getCriteriaField()
    {
        return self::LOCALIZATION;
    }

    /**
     * @return string
     */
    public function getCriteriaValueType()
    {
        return Localization::class;
    }
}
