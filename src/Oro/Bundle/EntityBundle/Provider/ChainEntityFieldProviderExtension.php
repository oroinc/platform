<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * This extension calls several leaf extensions in a chain until one is able to
 * handle the request.
 */
class ChainEntityFieldProviderExtension extends EntityFieldProviderExtension
{
    /** @var EntityFieldProviderExtension[]|array */
    protected $extensions = [];

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        foreach ($this->extensions as $extension) {
            if ($extension->isIgnoredField($metadata, $fieldName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        foreach ($this->extensions as $extension) {
            if ($extension->isIgnoredRelation($metadata, $associationName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param EntityFieldProviderExtension $provider
     */
    public function addExtension(EntityFieldProviderExtension $provider)
    {
        $this->extensions[] = $provider;
    }
} 