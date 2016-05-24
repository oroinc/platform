<?php

namespace Oro\Bundle\CalendarBundle\Autocomplete;

use Oro\Bundle\ActivityBundle\Autocomplete\ContextSearchHandler;

class AttendeeSearchHandler extends ContextSearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchAliases()
    {
        return ['oro_user'];
    }
}

