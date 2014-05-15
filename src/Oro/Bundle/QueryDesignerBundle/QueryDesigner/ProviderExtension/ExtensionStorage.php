<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner\ProviderExtension;

use Doctrine\ORM\Mapping\ClassMetadata;

class ExtensionStorage extends AbstractProviderExtension
{
    /** @var AbstractProviderExtension[]|array */
    protected $providerExtensions = [];

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        $result = false;
        foreach ($this->providerExtensions as $provider) {
            $result = $result || $provider->isIgnoredField($metadata, $fieldName);

            if ($result) {
                // no need to check other providers if field already determined as ignored
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        $result = false;
        foreach ($this->providerExtensions as $provider) {
            $result = $result || $provider->isIgnoredRelation($metadata, $associationName);

            if ($result) {
                // no need to check other providers if relation already determined as ignored
                break;
            }
        }

        return $result;
    }

    /**
     * @param AbstractProviderExtension $provider
     */
    public function addProviderExtension(AbstractProviderExtension $provider)
    {
        $this->providerExtensions[] = $provider;
    }

    /**
     * @return array|AbstractProviderExtension[]
     */
    public function getProviderExtensions()
    {
        return $this->providerExtensions;
    }
} 