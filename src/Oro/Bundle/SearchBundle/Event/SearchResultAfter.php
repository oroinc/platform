<?php

namespace Oro\Bundle\SearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class SearchResultAfter extends Event
{
    const NAME = 'oro_datagrid.search_datasource.result.after';
}
