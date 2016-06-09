<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Context\Initializer;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer\ElementFactoryInitializer;

class ElementFactoryInitializerTest extends \PHPUnit_Framework_TestCase
{
    public function testInitializeContext()
    {
        $elementFactory = $this
            ->getMockBuilder('Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $featureContext = $this->getMockBuilder('Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext')
            ->getMock();
        $featureContext->expects($this->once())->method('setElementFactory');

        $initializer = new ElementFactoryInitializer($elementFactory);
        $initializer->initializeContext($featureContext);
    }
}
