<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UserBundle\Entity\UserManager;

class UpdateResetAuthStatus implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension $extendExtension */
    protected $extendExtension;

    /**
     * {@inheritDoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $options = new OroOptions();
        $options->set(
            'enum',
            'immutable_codes',
            [
                UserManager::STATUS_ACTIVE,
                UserManager::STATUS_RESET
            ]
        );

        $schema->getTable($this->extendExtension->getNameGenerator()->generateEnumTableName('auth_status'))
            ->addOption(OroOptions::KEY, $options);

        $queries->addPostQuery(new UpdtaeExpiredStatusQuery($this->extendExtension));
    }
}
