<?php

namespace Oro\Bundle\AddressBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;

class OroAddressBundle extends Migration implements RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            $this->renameExtension->getRenameTableQuery(
                'oro_dictionary_country_translation',
                'oro_dictionary_country_trans'
            )
        );
        $queries->addQuery(
            $this->renameExtension->getRenameTableQuery(
                'oro_dictionary_region_translation',
                'oro_dictionary_region_trans'
            )
        );
    }
}
