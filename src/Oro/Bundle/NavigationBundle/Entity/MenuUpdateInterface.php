<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ScopeBundle\Entity\Scope;

interface MenuUpdateInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getKey();

    /**
     * @param string $key
     *
     * @return MenuUpdateInterface
     */
    public function setKey($key);

    /**
     * @return string
     */
    public function getParentKey();

    /**
     * @param string $parentKey
     *
     * @return MenuUpdateInterface
     */
    public function setParentKey($parentKey);

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getTitles();

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return MenuUpdateInterface
     */
    public function addTitle(LocalizedFallbackValue $title);

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return MenuUpdateInterface
     */
    public function removeTitle(LocalizedFallbackValue $title);

    /**
     * @param Localization $localization
     *
     * @return LocalizedFallbackValue
     */
    public function getTitle(Localization $localization = null);

    /**
     * @return LocalizedFallbackValue
     */
    public function getDefaultTitle();

    /**
     * @param string $value
     *
     * @return LocalizedFallbackValue
     */
    public function setDefaultTitle($value);

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getDescriptions();

    /**
     * @param LocalizedFallbackValue $description
     *
     * @return MenuUpdateInterface
     */
    public function addDescription(LocalizedFallbackValue $description);

    /**
     * @param LocalizedFallbackValue $description
     *
     * @return MenuUpdateInterface
     */
    public function removeDescription(LocalizedFallbackValue $description);

    /**
     * @param Localization $localization
     *
     * @return LocalizedFallbackValue
     */
    public function getDescription(Localization $localization = null);

    /**
     * @return LocalizedFallbackValue
     */
    public function getDefaultDescription();

    /**
     * @param string $value
     *
     * @return LocalizedFallbackValue
     */
    public function setDefaultDescription($value);

    /**
     * @return string
     */
    public function getUri();

    /**
     * @param string $uri
     *
     * @return MenuUpdateInterface
     */
    public function setUri($uri);

    /**
     * @return string
     */
    public function getMenu();

    /**
     * @param string $menu
     *
     * @return MenuUpdateInterface
     */
    public function setMenu($menu);

    /**
     * @return boolean
     */
    public function isActive();

    /**
     * @param boolean $active
     *
     * @return MenuUpdateInterface
     */
    public function setActive($active);

    /**
     * @return int
     */
    public function getPriority();

    /**
     * @param int $priority
     *
     * @return MenuUpdateInterface
     */
    public function setPriority($priority);

    /**
     * @return boolean
     */
    public function isDivider();

    /**
     * @param boolean $divider
     *
     * @return MenuUpdateInterface
     */
    public function setDivider($divider);

    /**
     * @return boolean
     */
    public function isCustom();

    /**
     * Check is new created item or it's update on existed item
     *
     * @param boolean $custom
     *
     * @return MenuUpdateInterface
     */
    public function setCustom($custom);

    /**
     * Get array of extra data that is not declared in MenuUpdateInterface model
     *
     * @return array
     */
    public function getExtras();

    /**
     * @return Scope
     */
    public function getScope();

    /**
     * @param Scope $scope
     *
     * @return MenuUpdateInterface
     */
    public function setScope(Scope $scope);
}
