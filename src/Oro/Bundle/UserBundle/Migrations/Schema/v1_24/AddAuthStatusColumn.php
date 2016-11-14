<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddAuthStatusColumn implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension $extendExtension */
    protected $extendExtension;

    /**
     * @param Schema $schema
     * @param ExtendExtension $extendExtension
     */
    public static function addAuthStatusField(Schema $schema, ExtendExtension $extendExtension)
    {
        $enumTable = $extendExtension->addEnumField(
            $schema,
            'oro_user',
            'auth_status',
            'auth_status'
        );

        $options = new OroOptions();
        $options->set(
            'enum',
            'immutable_codes',
            [
                'available',
                'locked',
            ]
        );

        $enumTable->addOption(OroOptions::KEY, $options);
    }

    /**
     * @param QueryBag $queries
     * @param ExtendExtension $extendExtension
     */
    public static function addEnumValues(QueryBag $queries, ExtendExtension $extendExtension)
    {
        $queries->addPostQuery(new InsertAuthStatusesQuery($extendExtension));
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addAuthStatusField($schema, $this->extendExtension);
        self::addEnumValues($queries, $this->extendExtension);
    }
}
