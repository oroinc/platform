<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Strategy;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;

class TranslationStrategyProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetStrategy()
    {
        /** @var TranslationStrategyInterface $defaultStrategy */
        $defaultStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');

        $provider = new TranslationStrategyProvider($defaultStrategy);
        $this->assertEquals($defaultStrategy, $provider->getStrategy());
    }

    public function testSetStrategy()
    {
        $defaultName = 'default';
        $customName = 'custom';

        /** @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject $defaultStrategy */
        $defaultStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');
        $defaultStrategy->expects($this->any())
            ->method('getName')
            ->willReturn($defaultName);
        /** @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject $customStrategy */
        $customStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');
        $customStrategy->expects($this->any())
            ->method('getName')
            ->willReturn($customName);

        $provider = new TranslationStrategyProvider($defaultStrategy);
        $this->assertEquals($defaultStrategy, $provider->getStrategy());
        $this->assertEquals($defaultName, $provider->getStrategy()->getName());
        $provider->setStrategy($customStrategy);
        $this->assertEquals($customStrategy, $provider->getStrategy());
        $this->assertEquals($customName, $provider->getStrategy()->getName());
    }
}
