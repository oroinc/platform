<?php

namespace Oro\Bundle\LocaleBundle\Extension;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * The extensions interface for providing current localization
 */
interface CurrentLocalizationExtensionInterface
{
    /**
     * @return Localization
     */
    public function getCurrentLocalization();
}
