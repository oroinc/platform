<?php

namespace Oro\Bundle\NavigationBundle\Model;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

abstract class ExtendMenuUpdate implements
    MenuUpdateInterface
{
    /**
     * Constructor
     *
     * The real implementation of this method is auto generated.
     *
     * IMPORTANT: If the derived class has own constructor it must call parent constructor.
     */
    public function __construct()
    {
    }

    /**
     * The real implementation of this method is auto generated.
     *
     * @param Localization|null $localization
     *
     * @return LocalizedFallbackValue
     */
    public function getTitle(Localization $localization = null)
    {
    }

    /**
     * The real implementation of this method is auto generated.
     *
     * @return LocalizedFallbackValue
     */
    public function getDefaultTitle()
    {
    }

    /**
     * The real implementation of this method is auto generated.
     *
     * @param string $value
     *
     * @return LocalizedFallbackValue
     */
    public function setDefaultTitle($value)
    {
    }

    /**
     * The real implementation of this method is auto generated.
     *
     * @param Localization|null $localization
     *
     * @return LocalizedFallbackValue
     */
    public function getDescription(Localization $localization = null)
    {
    }

    /**
     * The real implementation of this method is auto generated.
     *
     * @return LocalizedFallbackValue
     */
    public function getDefaultDescription()
    {
    }

    /**
     * The real implementation of this method is auto generated.
     *
     * @param string $value
     *
     * @return LocalizedFallbackValue
     */
    public function setDefaultDescription($value)
    {
    }
}
