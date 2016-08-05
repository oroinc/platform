<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;

class CurrentLocalizationProvider
{
    /** @var CurrentLocalizationExtensionInterface[] */
    protected $extensions = [];

    /** @var Localization */
    protected $currentLocalization = false;

    /**
     * @param string $name
     * @param CurrentLocalizationExtensionInterface $extension
     */
    public function addExtension($name, CurrentLocalizationExtensionInterface $extension)
    {
        $this->extensions[$name] = $extension;
    }

    /**
     * @return Localization|null
     */
    public function getCurrentLocalization()
    {
        if (false === $this->currentLocalization) {
            $this->currentLocalization = null;

            if (!$this->extensions) {
                return null;
            }

            foreach ($this->extensions as $extension) {
                /* @var $extension CurrentLocalizationExtensionInterface */
                if (null !== ($localization = $extension->getCurrentLocalization())) {
                    $this->currentLocalization = $localization;
                    break;
                }
            }
        }

        return $this->currentLocalization;
    }
}
