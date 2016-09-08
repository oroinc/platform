<?php

namespace Oro\Bundle\SearchBundle\Event;

use Oro\Bundle\DataGridBundle\Event\GridResultAfter;

class SearchResultAfter extends GridResultAfter
{
    const NAME = 'oro_datagrid.search_datasource.result.after';
}
