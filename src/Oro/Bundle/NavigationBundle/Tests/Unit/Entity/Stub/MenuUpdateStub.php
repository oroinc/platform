<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateTrait;

class MenuUpdateStub implements MenuUpdateInterface
{
    use MenuUpdateTrait;

    /** @var array */
    protected $extras = [];

    /** @var string */
    protected $defaultTitle;

    /** @var string */
    protected $defaultDescription;

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

    /**
     * {@inheritdoc}
     */
    public function getTitle(Localization $localization = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTitle()
    {
        return $this->defaultTitle;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultTitle($value)
    {
        $this->defaultTitle = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(Localization $localization = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDescription()
    {
        return $this->defaultTitle;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultDescription($value)
    {
        $this->defaultTitle = $value;

        return $this;
    }
}
