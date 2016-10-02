<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

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
     * @return string
     */
    public function getOwnershipType();

    /**
     * @param int $ownershipType
     *
     * @return MenuUpdateInterface
     */
    public function setOwnershipType($ownershipType);

    /**
     * @return int
     */
    public function getOwnerId();

    /**
     * @param int $ownerId
     *
     * @return MenuUpdateInterface
     */
    public function setOwnerId($ownerId);

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
     * @return boolean
     */
    public function isExistsInNavigationYml();

    /**
     * @param boolean $existsInNavigationYml
     *
     * @return MenuUpdateInterface
     */
    public function setExistsInNavigationYml($existsInNavigationYml);

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
     * Get array of extra data that is not declared in MenuUpdateInterface model
     *
     * @return array
     */
    public function getExtras();
}
