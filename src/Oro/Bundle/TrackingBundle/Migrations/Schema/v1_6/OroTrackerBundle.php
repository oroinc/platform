<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migrations\Schema\UpdateOwnershipTypeQuery;
use Oro\Bundle\TrackingBundle\Migration\Extension\IdentifierEventExtension;
use Oro\Bundle\TrackingBundle\Migration\Extension\IdentifierEventExtensionAwareInterface;


class OroTrackerBundle implements Migration, IdentifierEventExtensionAwareInterface
{
    /** @var IdentifierEventExtension */
    protected $extension;

    /**
     * Sets the
     *
     * @param IdentifierEventExtension $extension
     */
    public function setIdentifierEventExtension(IdentifierEventExtension $extension)
    {
        $this->extension = $extension;
    }


    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
       // $this->extension->addIdentifierAssociation($schema,'orocrm_magento_customer');
    }
}
