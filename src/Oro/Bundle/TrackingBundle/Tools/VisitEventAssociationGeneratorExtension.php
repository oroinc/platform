<?php

namespace Oro\Bundle\TrackingBundle\Tools;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;
use Oro\Bundle\TrackingBundle\Migration\Extension\VisitEventAssociationExtension;

class VisitEventAssociationGeneratorExtension extends AbstractAssociationEntityGeneratorExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        return
            $schema['class'] === 'Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent'
            && parent::supports($schema);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationKind()
    {
        return VisitEventAssociationExtension::ASSOCIATION_KIND;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationType()
    {
        return RelationType::MULTIPLE_MANY_TO_ONE;
    }
}
