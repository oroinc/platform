<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTranslationBundle implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new MigrateTranslationKeyPrefixQuery());

        //Update all unaffected by migration query translation keys
        $query = 'UPDATE oro_translation_key '
            . 'SET %1$s = CONCAT(\'oro\', SUBSTR(%1$s, 7)) '
            . 'WHERE %1$s LIKE \'%2$s\' OR %1$s LIKE \'%3$s\'';

        $queries->addQuery(
            sprintf(
                $query,
                $this->platform instanceof MySqlPlatform ? '`key`' : 'key',
                'orocrm.%',
                'oropro.%'
            )
        );
    }
}
