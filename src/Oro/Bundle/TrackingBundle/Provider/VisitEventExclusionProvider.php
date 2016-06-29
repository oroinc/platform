<?php

namespace Oro\Bundle\TrackingBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\AbstractExclusionProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TrackingBundle\Migration\Extension\VisitEventAssociationExtension;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore
 * relations which are a tracking visit event associations.
 */
class VisitEventExclusionProvider extends AbstractExclusionProvider
{
    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if ($metadata->name !== 'Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent') {
            return false;
        }

        $mapping = $metadata->getAssociationMapping($associationName);
        if (!$mapping['isOwningSide'] || !($mapping['type'] & ClassMetadata::MANY_TO_ONE)) {
            return false;
        }

        $visitEventAssociationName = ExtendHelper::buildAssociationName(
            $mapping['targetEntity'],
            VisitEventAssociationExtension::ASSOCIATION_KIND
        );

        return $associationName === $visitEventAssociationName;
    }
}
