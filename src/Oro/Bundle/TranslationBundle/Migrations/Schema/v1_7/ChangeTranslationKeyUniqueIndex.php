<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeTranslationKeyUniqueIndex implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_translation_key');
        if ($table->hasIndex('key_domain_uniq')) {
            $table->dropIndex('key_domain_uniq');
        }
        $table->addUniqueIndex(['domain', 'key'], 'oro_translation_key_uidx');
    }
}
