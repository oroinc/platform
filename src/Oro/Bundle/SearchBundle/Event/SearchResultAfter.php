<?php

namespace Oro\Bundle\SearchBundle\Event;

use Oro\Bundle\DataGridBundle\Event\AbstractResultAfter;

class SearchResultAfter extends AbstractResultAfter
{
    const NAME = 'oro_datagrid.search_datasource.result.after';
}
