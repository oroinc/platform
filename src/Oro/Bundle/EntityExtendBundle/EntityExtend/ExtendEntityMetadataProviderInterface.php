<?php

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

/**
 * Entity Field Extensions Iterator and Metadata provider interface
 */
interface ExtendEntityMetadataProviderInterface
{
    public function getExtendEntityMetadata(string $class): ?ConfigInterface;

    public function getExtendEntityFieldsMetadata(string $class): array;
}
