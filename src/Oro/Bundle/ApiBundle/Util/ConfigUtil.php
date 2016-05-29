<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Component\EntitySerializer\ConfigUtil as BaseConfigUtil;

class ConfigUtil extends BaseConfigUtil
{
    /** the name of an entity configuration section */
    const DEFINITION = 'definition';

    /** the name of filters configuration section */
    const FILTERS = 'filters';

    /** the name of sorters configuration section */
    const SORTERS = 'sorters';

    /** the name of actions configuration section */
    const ACTIONS = 'actions';

    /** the name of response status codes configuration section */
    const STATUS_CODES = 'status_codes';

    /** a flag indicates whether an entity configuration should be merged with a configuration of a parent entity */
    const INHERIT = 'inherit';
}
