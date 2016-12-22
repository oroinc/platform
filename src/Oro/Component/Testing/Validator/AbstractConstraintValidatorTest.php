<?php

namespace Oro\Component\Testing\Validator;

use Symfony\Component\Validator\Tests\Constraints\AbstractConstraintValidatorTest as ParentTest;

abstract class AbstractConstraintValidatorTest extends ParentTest
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected function getMock(
        $originalClassName,
        $methods = [],
        array $arguments = [],
        $mockClassName = '',
        $callOriginalConstructor = true,
        $callOriginalClone = true,
        $callAutoload = true,
        $cloneArguments = false,
        $callOriginalMethods = false,
        $proxyTarget = null
    ) {
        return $this->createMock($originalClassName);
    }
}
