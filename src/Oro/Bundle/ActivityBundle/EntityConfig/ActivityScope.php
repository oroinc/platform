<?php

namespace Oro\Bundle\ActivityBundle\EntityConfig;

class ActivityScope
{
    const GROUP_ACTIVITY   = 'activity';
    const ASSOCIATION_KIND = 'activity';

    // Name of entityconfig property to configure on which page activity should be displayed
    const SHOW_ON_PAGE = 'show_on_page';

    // Page type constants
    const NONE_PAGE = 0;
    const VIEW_PAGE = 1;
    const UPDATE_PAGE = 2;
    const VIEW_UPDATE_PAGES = 3;
}
