<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Context\Initializer;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer\OroPageObjectInitializer;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageFactory;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;

class OroPageObjectInitializerTest extends \PHPUnit\Framework\TestCase
{
    public function testInitializeContext(): void
    {
        $elementFactory = $this->createMock(OroElementFactory::class);
        $pageFactory = $this->createMock(OroPageFactory::class);
        $featureContext = $this->createMock(OroMainContext::class);
        $featureContext->expects(self::once())
            ->method('setElementFactory');

        $initializer = new OroPageObjectInitializer($elementFactory, $pageFactory);
        $initializer->initializeContext($featureContext);
    }
}
