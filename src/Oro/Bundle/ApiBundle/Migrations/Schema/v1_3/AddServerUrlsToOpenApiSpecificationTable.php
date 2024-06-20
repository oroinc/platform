<?php

namespace Oro\Bundle\ApiBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddServerUrlsToOpenApiSpecificationTable implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_api_openapi_specification');
        if ($table->hasColumn('server_urls')) {
            return;
        }

        $table->addColumn('server_urls', 'simple_array', ['comment' => '(DC2Type:simple_array)', 'notnull' => false]);
    }
}
