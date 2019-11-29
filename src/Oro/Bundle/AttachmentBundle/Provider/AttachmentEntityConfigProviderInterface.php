<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

/**
 * Provides an interface for classes that can be used to get attachment entity config.
 */
interface AttachmentEntityConfigProviderInterface
{
    /**
     * @param string $entityClass
     * @param string $fieldName
     * @return ConfigInterface|null
     */
    public function getFieldConfig(string $entityClass, string $fieldName): ?ConfigInterface;

    /**
     * @param string $entityClass
     * @return ConfigInterface|null
     */
    public function getEntityConfig(string $entityClass): ?ConfigInterface;
}
