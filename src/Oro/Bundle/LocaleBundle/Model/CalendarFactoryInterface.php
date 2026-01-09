<?php

namespace Oro\Bundle\LocaleBundle\Model;

/**
 * Defines the contract for creating calendar instances.
 *
 * Implementations of this interface are responsible for creating {@see Calendar} objects
 * configured with specific locale and language settings.
 */
interface CalendarFactoryInterface
{
    /**
     * Get calendar instance
     *
     * @param string|null $locale
     * @param string|null $language
     * @return Calendar
     */
    public function getCalendar($locale = null, $language = null);
}
