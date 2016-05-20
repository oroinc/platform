<?php

namespace Oro\Component\Layout\Tests\Unit\Block\OptionResolver;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

class OptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OptionsResolver
     */
    protected $optionResolver;

    public function setUp()
    {
        $this->optionResolver = new OptionsResolver();
    }

    public function testAddAllowedTypes()
    {
        $this->setExpectedException(
            'Oro\Component\Layout\Exception\LogicException',
            'Oro\Component\Layout\Block\OptionsResolver\OptionsResolver::addAllowedTypes method call is denied'
        );
        $this->optionResolver->addAllowedTypes('test', null);
    }

    public function testSetAllowedTypes()
    {
        $this->setExpectedException(
            'Oro\Component\Layout\Exception\LogicException',
            'Oro\Component\Layout\Block\OptionsResolver\OptionsResolver::setAllowedTypes method call is denied'
        );
        $this->optionResolver->setAllowedTypes('test', null);
    }
}
