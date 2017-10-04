<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Defines the interface of providers are used to get a text representation of an entity
 */
interface EntityNameProviderInterface
{
    /**
     * Some default formats
     */
    const FULL = 'full';
    const SHORT = 'short';

    /**
     * Returns a text representation of the given entity.
     *
     * @param string                   $format The representation format, for example full, short, etc.
     * @param string|null|Localization $locale The representation locale.
     * @param object                   $entity The entity object
     *
     * @return string A text representation of an entity or FALSE if this provider cannot return reliable result
     */
    public function getName($format, $locale, $entity);

    /**
     * Returns a DQL expression that can be used to get a text representation of the given type of entities.
     *
     * @param string                   $format    The representation format, for example full, short, etc.
     * @param string|null|Localization $locale    The representation locale.
     * @param string                   $className The FQCN of the entity
     * @param string                   $alias     The alias in SELECT or JOIN statement
     *
     * @return string A DQL expression or FALSE if this provider cannot return reliable result
     */
    public function getNameDQL($format, $locale, $className, $alias);
}
