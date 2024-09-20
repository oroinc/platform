<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ContextInterface;

class ValidateClassExistenceApplicableChecker implements ApplicableCheckerInterface
{
    private const CLASS_ATTRIBUTES = ['class', 'parentClass'];

    private EntityClassProviderInterface $enumOptionEntityClassProvider;
    private ?array $enumOptionEntityClassNames = null;

    public function __construct(EntityClassProviderInterface $enumOptionEntityClassProvider)
    {
        $this->enumOptionEntityClassProvider = $enumOptionEntityClassProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes): int
    {
        foreach (self::CLASS_ATTRIBUTES as $attributeName) {
            if (isset($processorAttributes[$attributeName])
                && !$this->isClassExists($processorAttributes[$attributeName])
            ) {
                throw new \InvalidArgumentException(sprintf(
                    'The class "%s" specified in the attribute "%s" does not exist.',
                    $processorAttributes[$attributeName],
                    $attributeName
                ));
            }
        }

        return self::APPLICABLE;
    }

    private function isClassExists(string $className): bool
    {
        if (ExtendHelper::isOutdatedEnumOptionEntity($className)) {
            if (null === $this->enumOptionEntityClassNames) {
                $this->enumOptionEntityClassNames = $this->enumOptionEntityClassProvider->getClassNames();
            }

            return \in_array($className, $this->enumOptionEntityClassNames, true);
        }

        return class_exists($className);
    }
}
