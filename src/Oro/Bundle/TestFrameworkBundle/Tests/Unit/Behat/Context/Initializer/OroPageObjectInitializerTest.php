<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Context\Initializer;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer\OroPageObjectInitializer;

class OroPageObjectInitializerTest extends \PHPUnit\Framework\TestCase
{
    public function testInitializeContext()
    {
        $elementFactory = $this
            ->getMockBuilder('Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $pageFactory = $this
            ->getMockBuilder('Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageFactory')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $featureContext = $this->getMockBuilder('Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext')
            ->getMock();
        $featureContext->expects($this->once())->method('setElementFactory');

        $initializer = new OroPageObjectInitializer($elementFactory, $pageFactory);
        $initializer->initializeContext($featureContext);
    }
}
