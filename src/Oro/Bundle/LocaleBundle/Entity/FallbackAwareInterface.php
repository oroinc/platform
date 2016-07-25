<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\LocaleBundle\Entity\Localization;

interface FallbackAwareInterface
{
    /**
     * @param Collection $values
     * @param Localization|null $localization
     */
    public function getFallbackValue(Collection $values, Localization $localization = null);

    /**
     * @param Collection $values
     * @return Localization|null
     */
    public function getDefaultFallbackValue(Collection $values);

    /**
     * @param Collection $values
     * @param string $value
     * @return $this
     */
    public function setDefaultFallbackValue(Collection $values, $value);
}
