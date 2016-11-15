<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Cache;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\TranslationsCacheWarmer as InnerCacheWarmer;

use Oro\Bundle\TranslationBundle\Cache\TranslationCacheWarmer;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;

class TranslationCacheWarmerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InnerCacheWarmer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $innerWarmer;

    /**
     * @var TranslationStrategyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $strategyProvider;

    /**
     * @var TranslationCacheWarmer
     */
    protected $warmer;

    protected function setUp()
    {
        $this->innerWarmer = $this->getMockBuilder(InnerCacheWarmer::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->strategyProvider = $this->getMockBuilder(TranslationStrategyProvider::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->warmer = new TranslationCacheWarmer($this->innerWarmer, $this->strategyProvider);
    }

    public function testWarmUp()
    {
        $directory = '/cache_dir';
        $defaultStrategyName = 'default';
        $mixingStrategyName = 'mix';

        $defaultStrategy = $this->getStrategy($defaultStrategyName);
        $mixingStrategy = $this->getStrategy($mixingStrategyName);

        $this->strategyProvider->expects($this->at(0))->method('getStrategy')
            ->willReturn($defaultStrategy);

        $this->strategyProvider->expects($this->at(1))->method('getStrategies')
            ->willReturn(['default' => $defaultStrategy, 'mix' => $mixingStrategy]);

        $this->strategyProvider->expects($this->at(2))->method('setStrategy')->with($defaultStrategy);
        $this->strategyProvider->expects($this->at(3))->method('setStrategy')->with($mixingStrategy);

        $this->innerWarmer->expects($this->exactly(2))->method('warmUp')->with($directory);

        $this->strategyProvider->expects($this->at(4))->method('setStrategy')->with($defaultStrategy);

        $this->warmer->warmUp($directory);
    }

    /**
     * @param bool $isOptional
     * @dataProvider optionalDataProvider
     */
    public function testIsOptional($isOptional)
    {
        $this->innerWarmer->expects($this->once())
            ->method('isOptional')
            ->willReturn($isOptional);

        $this->assertEquals($isOptional, $this->warmer->isOptional());
    }

    public function optionalDataProvider()
    {
        return [
            'optional' => [true],
            'not optional' => [false],
        ];
    }

    /**
     * @param string $name
     * @return TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStrategy($name)
    {
        $strategy = $this->getMock(TranslationStrategyInterface::class);
        $strategy->expects($this->any())->method('isApplicable')->willReturn(true);
        $strategy->expects($this->any())->method('getName')->willReturn($name);

        return $strategy;
    }
}
