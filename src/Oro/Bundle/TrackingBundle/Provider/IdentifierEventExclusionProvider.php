<?php

namespace Oro\Bundle\TrackingBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TrackingBundle\Migration\Extension\IdentifierEventExtension;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore
 * relations which are a tracking visit associations.
 */
class IdentifierEventExclusionProvider implements ExclusionProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if ($metadata->name !== 'Oro\Bundle\TrackingBundle\Entity\TrackingVisit') {
            return false;
        }

        $mapping = $metadata->getAssociationMapping($associationName);
        if (!$mapping['isOwningSide'] || !($mapping['type'] & ClassMetadata::MANY_TO_ONE)) {
            return false;
        }

        $identifierEventAssociationName = ExtendHelper::buildAssociationName(
            $mapping['targetEntity'],
            IdentifierEventExtension::ASSOCIATION_KIND
        );

        return $associationName === $identifierEventAssociationName;
    }
}
