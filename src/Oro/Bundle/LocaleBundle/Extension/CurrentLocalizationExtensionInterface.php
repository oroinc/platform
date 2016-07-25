<?php

namespace Oro\Bundle\LocaleBundle\Extension;

use Oro\Bundle\LocaleBundle\Entity\Localization;

interface CurrentLocalizationExtensionInterface
{
    /**
     * @return Localization
     */
    public function getCurrentLocalization();
}
