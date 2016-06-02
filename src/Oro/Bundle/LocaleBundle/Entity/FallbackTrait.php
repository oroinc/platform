<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

trait FallbackTrait
{
    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Locale|null $locale
     * @return LocalizedFallbackValue
     */
    protected function getLocalizedFallbackValue(Collection $values, Locale $locale = null)
    {
        $filteredValues = $values->filter(
            function (LocalizedFallbackValue $title) use ($locale) {
                return $locale === $title->getLocale();
            }
        );

        // TODO: implement with fallback
        if ($filteredValues->count() > 1) {
            $localeTitle = $locale ? $locale->getTitle() : 'default';
            throw new \LogicException(sprintf('There must be only one %s title', $localeTitle));
        }
        $value = $filteredValues->first();
        if (!$value && $locale !== null) {
            $value = $this->getLocalizedFallbackValue($values); // get default value
        }

        return $value;
    }
}
