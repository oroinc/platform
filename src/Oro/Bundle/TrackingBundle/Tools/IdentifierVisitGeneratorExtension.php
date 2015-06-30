<?php

namespace Oro\Bundle\TrackingBundle\Tools;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;
use Oro\Bundle\TrackingBundle\Migration\Extension\IdentifierEventExtension;

class IdentifierVisitGeneratorExtension extends AbstractAssociationEntityGeneratorExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        return
            $schema['class'] === 'Oro\Bundle\TrackingBundle\Entity\TrackingVisit'
            && parent::supports($schema);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationKind()
    {
        return IdentifierEventExtension::ASSOCIATION_KIND;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationType()
    {
        return RelationType::MANY_TO_ONE;
    }
}
