<?php

namespace Oro\Bundle\EntityExtendBundle\Test;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendEntityMetadataProviderInterface;

/**
 * This implementation of {@see ExtendEntityMetadataProviderInterface} is used in unit tests
 * to be able to use {@see ExtendedEntityFieldsProcessor} in these tests.
 */
class ExtendEntityTestMetadataProvider implements ExtendEntityMetadataProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getExtendEntityMetadata(string $class): ?ConfigInterface
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getExtendEntityFieldsMetadata(string $class): array
    {
        return [];
    }
}
