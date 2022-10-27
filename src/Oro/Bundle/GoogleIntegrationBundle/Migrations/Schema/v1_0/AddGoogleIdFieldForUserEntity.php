<?php

namespace Oro\Bundle\GoogleIntegrationBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds "googleId" field to User entity.
 */
class AddGoogleIdFieldForUserEntity implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $userTable = $schema->getTable('oro_user');
        if (!$userTable->hasColumn('googleId')) {
            $userTable->addColumn('googleId', 'string', [
                'oro_options' => [
                    'extend' => ['owner' => ExtendScope::OWNER_SYSTEM]
                ],
                'notnull'     => false
            ]);
        }
    }
}
