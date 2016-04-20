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
        $defaultLocale = 'en';
        $customLocale = 'en_US';

        /** @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject $defaultStrategy */
        $defaultStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');
        $defaultStrategy->expects($this->any())
            ->method('getCurrentLocale')
            ->willReturn($defaultLocale);
        /** @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject $customStrategy */
        $customStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');
        $customStrategy->expects($this->any())
            ->method('getCurrentLocale')
            ->willReturn($customLocale);

        $provider = new TranslationStrategyProvider($defaultStrategy);
        $this->assertEquals($defaultStrategy, $provider->getStrategy());
        $this->assertEquals($defaultLocale, $provider->getStrategy()->getCurrentLocale());
        $provider->setStrategy($customStrategy);
        $this->assertEquals($customStrategy, $provider->getStrategy());
        $this->assertEquals($customLocale, $provider->getStrategy()->getCurrentLocale());
    }
}
