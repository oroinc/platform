<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Cache;

use Oro\Bundle\TranslationBundle\Cache\TranslationCacheWarmer;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\TranslationsCacheWarmer as InnerCacheWarmer;

class TranslationCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InnerCacheWarmer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $innerWarmer;

    /**
     * @var TranslationStrategyProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $strategyProvider;

    /**
     * @var TranslationCacheWarmer
     */
    protected $warmer;

    /**
     * {@inheritdoc}
     */
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

    /**
     * @return array
     */
    public function optionalDataProvider()
    {
        return [
            'optional' => [true],
            'not optional' => [false],
        ];
    }

    /**
     * @param string $name
     * @return TranslationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getStrategy($name)
    {
        $strategy = $this->createMock(TranslationStrategyInterface::class);
        $strategy->expects($this->any())->method('isApplicable')->willReturn(true);
        $strategy->expects($this->any())->method('getName')->willReturn($name);

        return $strategy;
    }
}
