<?php

namespace Oro\Bundle\SSOBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSSOBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $userTable = $schema->getTable('oro_user');
        $userTable->addColumn('googleId', 'string', [
            'oro_options' => [
                'extend' => ['owner' => ExtendScope::OWNER_SYSTEM]
            ],
            'notnull' => false
        ]);
    }
}
