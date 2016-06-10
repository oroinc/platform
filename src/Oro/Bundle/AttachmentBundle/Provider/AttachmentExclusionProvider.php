<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\AbstractExclusionProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore
 * relations which are an attachment associations.
 */
class AttachmentExclusionProvider extends AbstractExclusionProvider
{
    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if ($metadata->name !== 'Oro\Bundle\AttachmentBundle\Entity\Attachment') {
            return false;
        }

        $mapping = $metadata->getAssociationMapping($associationName);
        if (!$mapping['isOwningSide'] || !($mapping['type'] & ClassMetadata::MANY_TO_ONE)) {
            return false;
        }

        return $associationName === ExtendHelper::buildAssociationName($mapping['targetEntity']);
    }
}
