<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Interface for menu update entity.
 */
interface MenuUpdateInterface
{
    public const TITLES = 'titles';
    public const DESCRIPTION = 'description';
    public const IS_DIVIDER = 'divider';
    public const IS_TRANSLATE_DISABLED = 'translate_disabled';
    public const IS_CUSTOM = 'custom';
    public const IS_SYNTHETIC = 'synthetic';
    public const POSITION = 'position';
    public const ICON = 'icon';

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
     * Generates key, if it's not defined
     */
    public function generateKey();

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
     * Synthetic is a non-custom menu item that should remain in tree even if target item does not exist.
     */
    public function isSynthetic(): bool;

    public function setSynthetic(bool $synthetic): self;

    /**
     * Get array of link attributes
     */
    public function getLinkAttributes(): array;

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
