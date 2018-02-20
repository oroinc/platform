<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateTrait;

class MenuUpdateStub implements MenuUpdateInterface
{
    use MenuUpdateTrait;

    /** @var array */
    protected $extras = [];

    /**
     * MenuUpdateStub constructor.
     */
    public function __construct()
    {
        $this->titles = new ArrayCollection();
        $this->descriptions = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * @param array $extras
     *
     * @return MenuUpdateStub
     */
    public function setExtras(array $extras)
    {
        $this->extras = $extras;

        return $this;
    }

    public function setImage($value)
    {
        $this->image = $value;

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setDefaultTitle($value)
    {
        return $this->setDefaultFallbackValue($this->titles, $value);
    }

    /**
     * @param Localization|null $localization
     * @return LocalizedFallbackValue|null
     */
    public function getTitle(\Oro\Bundle\LocaleBundle\Entity\Localization $localization = null)
    {
        return $this->getFallbackValue($this->titles, $localization);
    }

    /**
     * @param Localization|null $localization
     * @return LocalizedFallbackValue|null
     */
    public function getDescription(\Oro\Bundle\LocaleBundle\Entity\Localization $localization = null)
    {
        return $this->getFallbackValue($this->descriptions, $localization);
    }

    /**
     * @return LocalizedFallbackValue|null
     */
    public function getDefaultTitle()
    {
        return $this->getDefaultFallbackValue($this->titles);
    }

    /**
     * @return LocalizedFallbackValue|null
     */
    public function getDefaultDescription()
    {
        return $this->getDefaultFallbackValue($this->descriptions);
    }
}
