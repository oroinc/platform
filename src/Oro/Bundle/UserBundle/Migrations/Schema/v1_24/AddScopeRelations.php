<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddScopeRelations implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    private $extendExtension;

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
        $this->addRelationsToScope($schema);
    }

    /**
     * @param Schema $schema
     */
    private function addRelationsToScope(Schema $schema)
    {
        if ($schema->hasTable('oro_scope')) {
            $this->extendExtension->addManyToOneRelation(
                $schema,
                'oro_scope',
                'user',
                'oro_user',
                'id',
                [
                    'extend' => [
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'cascade' => ['all'],
                        'on_delete' => 'CASCADE',
                        'nullable' => true
                    ]
                ],
                RelationType::MANY_TO_ONE
            );
        }
    }
}
