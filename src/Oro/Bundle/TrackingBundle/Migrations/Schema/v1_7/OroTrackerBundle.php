<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\TrackingBundle\Migration\Extension\VisitEventAssociationExtension;
use Oro\Bundle\TrackingBundle\Migration\Extension\VisitEventAssociationExtensionAwareInterface;

/**
 * todo: Move it to the magento bundle
 */
class OroTrackerBundle implements Migration, VisitEventAssociationExtensionAwareInterface
{
    /** @var VisitEventAssociationExtension */
    protected $extension;

    public function setVisitEventAssociationExtension(VisitEventAssociationExtension $extension)
    {
        $this->extension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->extension->addVisitEventAssociation($schema, 'orocrm_magento_cart');
        $this->extension->addVisitEventAssociation($schema, 'orocrm_magento_customer');
        $this->extension->addVisitEventAssociation($schema, 'orocrm_magento_order');
        $this->extension->addVisitEventAssociation($schema, 'orocrm_magento_product');
    }
}
