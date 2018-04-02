<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroLocaleBundle implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schemaBefore = clone $schema;

        $table = $schemaBefore->getTable('oro_localization');
        $table->addColumn('language_id', 'integer', ['notnull' => false]);

        foreach ($this->getSchemaDiff($schema, $schemaBefore) as $query) {
            $queries->addQuery($query);
        }

        $queries->addQuery(new CreateRelatedLanguagesQuery());
        $queries->addQuery(
            'UPDATE oro_localization SET language_id = ' .
            '(SELECT id FROM oro_language WHERE oro_localization.language_code = oro_language.code LIMIT 1)'
        );

        $schemaAfter = clone $schemaBefore;

        $table = $schemaAfter->getTable('oro_localization');
        $table->changeColumn('language_id', ['notnull' => true]);
        $table->dropColumn('language_code');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_language'),
            ['language_id'],
            ['id'],
            ['onDelete' => 'RESTRICT', 'onUpdate' => null]
        );

        foreach ($this->getSchemaDiff($schemaBefore, $schemaAfter) as $query) {
            $queries->addQuery($query);
        }

        $queries->addQuery(
            new RemoveFieldQuery(
                Localization::class,
                'languageCode'
            )
        );
    }

    /**
     * @param Schema $schema
     * @param Schema $toSchema
     *
     * @return array
     */
    protected function getSchemaDiff(Schema $schema, Schema $toSchema)
    {
        $comparator = new Comparator();
        return $comparator->compare($schema, $toSchema)->toSql($this->platform);
    }
}
