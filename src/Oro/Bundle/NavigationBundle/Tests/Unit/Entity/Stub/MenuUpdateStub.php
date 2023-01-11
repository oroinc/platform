<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateTrait;

class MenuUpdateStub implements MenuUpdateInterface
{
    use MenuUpdateTrait;

    protected array $extras = [];

    private array $linkAttributes = [];

    public function __construct(?int $id = null)
    {
        if ($id !== null) {
            $this->id = $id;
        }

        $this->titles = new ArrayCollection();
        $this->descriptions = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtras(): array
    {
        return $this->extras;
    }

    public function setExtras(array $extras): self
    {
        $this->extras = $extras;

        return $this;
    }

    public function setImage($value): self
    {
        $this->image = $value;

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setDefaultTitle($value): self
    {
        return $this->setDefaultFallbackValue($this->titles, $value);
    }

    /**
     * @param Localization|null $localization
     * @return LocalizedFallbackValue|null
     */
    public function getTitle(Localization $localization = null)
    {
        return $this->getFallbackValue($this->titles, $localization);
    }

    public function getTitles(): Collection
    {
        return $this->titles;
    }

    public function setTitles(Collection $titles): self
    {
        $this->titles = $titles;

        return $this;
    }

    /**
     * @param Localization|null $localization
     * @return LocalizedFallbackValue|null
     */
    public function getDescription(Localization $localization = null)
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

    public function setLinkAttributes(array $linkAttributes): self
    {
        $this->linkAttributes = $linkAttributes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkAttributes(): array
    {
        return $this->linkAttributes;
    }
}
