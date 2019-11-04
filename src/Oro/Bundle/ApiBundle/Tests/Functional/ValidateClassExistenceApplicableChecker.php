<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ContextInterface;

class ValidateClassExistenceApplicableChecker implements ApplicableCheckerInterface
{
    /** @var array */
    private $classAttributes = ['class', 'parentClass'];

    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
    {
        foreach ($this->classAttributes as $attributeName) {
            if (isset($processorAttributes[$attributeName])
                && !class_exists($processorAttributes[$attributeName])
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
}
