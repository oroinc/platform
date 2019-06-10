<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;

/**
 * Provides localization depending on extension set and executed
 */
class CurrentLocalizationProvider implements LocalizationProviderInterface
{
    /** @var iterable|CurrentLocalizationExtensionInterface[] */
    private $extensions;

    /** @var Localization|null */
    private $currentLocalization = false;

    /**
     * @param iterable|CurrentLocalizationExtensionInterface[] $extensions
     */
    public function __construct(iterable $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentLocalization()
    {
        if (false === $this->currentLocalization) {
            $this->currentLocalization = null;
            foreach ($this->extensions as $extension) {
                $localization = $extension->getCurrentLocalization();
                if (null !== $localization) {
                    $this->currentLocalization = $localization;
                    break;
                }
            }
        }

        return $this->currentLocalization;
    }
}
