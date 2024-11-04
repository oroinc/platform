<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddScopeRelations implements Migration, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addRelationsToScope($schema);
    }

    private function addRelationsToScope(Schema $schema)
    {
        if ($schema->hasTable('oro_scope')) {
            $this->extendExtension->addManyToOneRelation(
                $schema,
                'oro_scope',
                'organization',
                'oro_organization',
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
