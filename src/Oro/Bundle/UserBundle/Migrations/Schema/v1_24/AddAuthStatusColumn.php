<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UserBundle\Entity\UserManager;

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
                UserManager::STATUS_ACTIVE,
                UserManager::STATUS_EXPIRED,
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
        self::addAuthStatusFieldAndValues($schema, $queries, $this->extendExtension);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param ExtendExtension $extendExtension
     */
    public static function addAuthStatusFieldAndValues(
        Schema $schema,
        QueryBag $queries,
        ExtendExtension $extendExtension
    ) {
        self::addAuthStatusField($schema, $extendExtension);
        self::addEnumValues($queries, $extendExtension);
    }
}
