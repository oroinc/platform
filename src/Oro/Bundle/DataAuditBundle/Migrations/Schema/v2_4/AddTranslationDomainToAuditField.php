<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v2_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddTranslationDomainToAuditField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_audit_field');

        if ($table->hasColumn('translation_domain')) {
            return;
        }

        $table->addColumn('translation_domain', 'string', ['length' => 100, 'notnull' => false]);
    }
}
