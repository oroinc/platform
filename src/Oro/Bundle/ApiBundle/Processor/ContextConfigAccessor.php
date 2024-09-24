<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * Provides the configuration of API resource from the execution context
 * of processors responsible for primary resource actions.
 */
class ContextConfigAccessor implements ConfigAccessorInterface
{
    private Context $context;
    private array $resolvedClassNames = [];

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    #[\Override]
    public function getConfig(string $className): ?EntityDefinitionConfig
    {
        $config = $this->context->getConfig();

        return is_a($this->context->getClassName(), $this->resolveClassName($className, $config), true)
            ? $config
            : null;
    }

    private function resolveClassName(string $className, ?EntityDefinitionConfig $config): string
    {
        if (isset($this->resolvedClassNames[$className])) {
            return $this->resolvedClassNames[$className];
        }

        $resolvedClassName = $className;
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
