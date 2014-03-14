<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type;

abstract class AbstractDateTypeTestCase extends AbstractTypeTestCase
{
    /**
     * @dataProvider setDefaultOptionsDataProvider
     * @param array $defaultOptions
     * @param array $requiredOptions
     */
    public function testSetDefaultOptions(array $defaultOptions, array $requiredOptions = array())
    {
        $resolver = $this->createMockOptionsResolver();

        $resolver->expects($this->at(0))
            ->method('setDefaults')
            ->will($this->returnSelf());

        $resolver->expects($this->at(1))
            ->method('setDefaults')
            ->with($defaultOptions)
            ->will($this->returnSelf());

        $this->getTestFormType()->setDefaultOptions($resolver);
    }
}
