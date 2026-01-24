<?php

namespace Oro\Bundle\NavigationBundle\Model;

/**
 * Defines the contract for objects that maintain URL awareness.
 *
 * Implementations of this interface represent entities or objects that have an associated URL,
 * such as navigation items, shortcuts, or bookmarks. This interface provides a standard way
 * to get and set URLs on objects, enabling consistent URL handling across the navigation system.
 */
interface UrlAwareInterface
{
    /**
     * @return string
     */
    public function getUrl();

    /**
     * @param string $url
     *
     * @return UrlAwareInterface
     */
    public function setUrl($url);
}
