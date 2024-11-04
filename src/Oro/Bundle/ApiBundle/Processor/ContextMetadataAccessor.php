<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;

/**
 * Provides the metadata of API resource from the execution context
 * of processors responsible for primary resource actions.
 */
class ContextMetadataAccessor implements MetadataAccessorInterface
{
    private Context $context;
    private array $resolvedClassNames = [];

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    #[\Override]
    public function getMetadata(string $className): ?EntityMetadata
    {
        return is_a($this->context->getClassName(), $this->resolveClassName($className), true)
            ? $this->context->getMetadata()
            : null;
    }

    private function resolveClassName(string $className): string
    {
        if (isset($this->resolvedClassNames[$className])) {
            return $this->resolvedClassNames[$className];
        }

        $resolvedClassName = $className;
        $config = $this->context->getConfig();
        if (null !== $config) {
            $formDataClass = $config->getFormOption('data_class');
            if ($formDataClass && $formDataClass === $className) {
                $resolvedClassName = $this->context->getClassName();
            }
        }
        $this->resolvedClassNames[$className] = $resolvedClassName;

        return $resolvedClassName;
    }
}
