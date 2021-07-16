<?php

namespace Oro\Bundle\ScopeBundle\Migration\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ScopeBundle\Migration\AddCommentToRoHashManager;
use Oro\Bundle\ScopeBundle\Migration\Query\AddTriggerToRowHashQuery;

/**
 * Added trigger to oro_scope table
 */
class AddTriggerToRowHashColumn implements Migration
{
    /**
     * @var AddCommentToRoHashManager
     */
    protected $manager;

    public function __construct(AddCommentToRoHashManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_scope');
        if (!$table->hasColumn('row_hash')) {
            return;
        }

        $queries->addQuery(new AddTriggerToRowHashQuery());
        $this->manager->addRowHashComment($schema);
    }
}
