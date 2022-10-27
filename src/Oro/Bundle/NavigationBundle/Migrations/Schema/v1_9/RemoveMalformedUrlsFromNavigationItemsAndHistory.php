<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveMalformedUrlsFromNavigationItemsAndHistory implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new RemoveMalformedUrlsQuery('oro_navigation_item', 'url'));
        $queries->addPostQuery(new RemoveMalformedUrlsQuery('oro_navigation_history', 'url'));
    }
}
