<?php

namespace Oro\Bundle\SearchBundle\Event;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

class SearchResultAfter extends OrmResultAfter
{
    const NAME = 'oro_datagrid.search_datasource.result.after';
}
