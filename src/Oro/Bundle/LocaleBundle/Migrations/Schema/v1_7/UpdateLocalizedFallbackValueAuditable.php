<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateLocalizedFallbackValueAuditable implements Migration
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                LocalizedFallbackValue::class,
                'dataaudit',
                'auditable',
                true
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                LocalizedFallbackValue::class,
                'string',
                'dataaudit',
                'auditable',
                true
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                LocalizedFallbackValue::class,
                'text',
                'dataaudit',
                'auditable',
                true
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                LocalizedFallbackValue::class,
                'wysiwyg',
                'dataaudit',
                'auditable',
                true
            )
        );
    }
}
