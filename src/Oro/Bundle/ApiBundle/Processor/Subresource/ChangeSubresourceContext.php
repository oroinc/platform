<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The execution context for processors for "update_subresource", "add_subresource"
 * and "delete_subresource" actions.
 */
class ChangeSubresourceContext extends ChangeRelationshipContext
{
    private string|null|false $requestClassName = false;
    private EntityDefinitionConfig|null|bool $requestConfig = false;
    private EntityMetadata|null|bool $requestMetadata = false;

    /**
     * Gets FQCN of the request entity.
     */
    public function getRequestClassName(): ?string
    {
        if (false === $this->requestClassName) {
            $this->requestClassName = $this->getParentConfig()?->get(ConfigUtil::REQUEST_TARGET_CLASS);
        }

        return $this->requestClassName ?? $this->getClassName();
    }

    /**
     * Sets FQCN of the request entity.
     */
    public function setRequestClassName(?string $className): void
    {
        $this->requestClassName = $className;
    }

    /**
     * Gets a configuration of the request entity.
     */
    public function getRequestConfig(): ?EntityDefinitionConfig
    {
        if (false === $this->requestConfig) {
            $this->requestConfig = true;
            $entityClass = $this->getRequestClassName();
            if ($entityClass && $entityClass !== $this->getClassName()) {
                $requestConfig = $this->loadEntityConfig($entityClass, $this->getConfigExtras())->getDefinition();
                if (null !== $requestConfig) {
                    $this->requestConfig = $requestConfig;
                }
            } else {
                $this->requestConfig = null;
            }
        }

        if (null === $this->requestConfig) {
            return $this->getConfig();
        }
        if (true === $this->requestConfig) {
            return null;
        }

        return $this->requestConfig;
    }

    /**
     * Sets a configuration of the request entity.
     */
    public function setRequestConfig(?EntityDefinitionConfig $definition): void
    {
        $this->requestConfig = $definition;
    }

    /**
     * Gets metadata of the request entity.
     */
    public function getRequestMetadata(): ?EntityMetadata
    {
        if (false === $this->requestMetadata) {
            $this->requestMetadata = true;
            $entityClass = $this->getRequestClassName();
            if ($entityClass && $entityClass !== $this->getClassName()) {
                $config = $this->getRequestConfig();
                if (null !== $config) {
                    $metadata = $this->metadataProvider->getMetadata(
                        $entityClass,
                        $this->getVersion(),
                        $this->getRequestType(),
                        $config,
                        $this->getMetadataExtras()
                    );
                    if (null !== $metadata) {
                        $this->initializeMetadata($metadata);
                        $this->requestMetadata = $metadata;
                    } else {
                        $this->requestMetadata = null;
                    }
                }
            } else {
                $this->requestMetadata = null;
            }
        }

        if (null === $this->requestMetadata) {
            return $this->getMetadata();
        }
        if (true === $this->requestMetadata) {
            return null;
        }

        return $this->requestMetadata;
    }

    /**
     * Sets metadata of the request entity.
     */
    public function setRequestMetadata(?EntityMetadata $metadata): void
    {
        $this->requestMetadata = $metadata;
    }
}
