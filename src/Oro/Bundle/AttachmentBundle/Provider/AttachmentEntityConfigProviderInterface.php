<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

/**
 * Provides an interface for classes that can be used to get attachment entity config.
 */
interface AttachmentEntityConfigProviderInterface
{
    public function getFieldConfig(string $entityClass, string $fieldName): ?ConfigInterface;

    public function getEntityConfig(string $entityClass): ?ConfigInterface;
}
