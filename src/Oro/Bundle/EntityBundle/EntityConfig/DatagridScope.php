<?php

namespace Oro\Bundle\EntityBundle\EntityConfig;

class DatagridScope
{
    const IS_VISIBLE_FALSE     = 0; // do not show on grid and do not allow to manage
    const IS_VISIBLE_TRUE      = 1; // show on grid by default and allow to manage
    const IS_VISIBLE_MANDATORY = 2; // show on grid and do not allow to manage
    const IS_VISIBLE_HIDDEN    = 3; // do not show on grid and allow to manage
}
