<?php

namespace Oro\Bundle\ActivityBundle\EntityConfig;

/**
 * ActivityScope entity config
 *
 */
class ActivityScope
{
    public const GROUP_ACTIVITY   = 'activity';
    public const ASSOCIATION_KIND = 'activity';

    // Name of entityconfig property to configure on which page activity should be displayed
    public const SHOW_ON_PAGE = 'show_on_page';

    // Page type constants
    public const NONE_PAGE = 0;
    public const VIEW_PAGE = 1;
    public const UPDATE_PAGE = 2;
    public const VIEW_UPDATE_PAGES = 3;

    /**
     * Checks whether activities can be displayed on a given page type.
     */
    public static function isAllowedOnPage(int $pageType, int $configValue): bool
    {
        return
            $configValue !== ActivityScope::NONE_PAGE
            && ($configValue & $pageType) === $pageType;
    }
}
