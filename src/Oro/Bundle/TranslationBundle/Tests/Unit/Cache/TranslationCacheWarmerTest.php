<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Cache;

use Oro\Bundle\TranslationBundle\Cache\TranslationCacheWarmer;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\TranslationsCacheWarmer as InnerCacheWarmer;

class TranslationCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /** @var InnerCacheWarmer|\PHPUnit\Framework\MockObject\MockObject */
    private $innerWarmer;

    /** @var TranslationStrategyProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $strategyProvider;

    /** @var TranslationCacheWarmer */
    private $warmer;

    protected function setUp(): void
    {
        $this->innerWarmer = $this->createMock(InnerCacheWarmer::class);
        $this->strategyProvider = $this->createMock(TranslationStrategyProvider::class);

        $this->warmer = new TranslationCacheWarmer($this->innerWarmer, $this->strategyProvider);
    }

    public function testWarmUp()
    {
        $cacheDir = '/cache_dir';

        $defaultStrategy = $this->createMock(TranslationStrategyInterface::class);
        $mixingStrategy = $this->createMock(TranslationStrategyInterface::class);

        $calls = [];

        $this->strategyProvider->expects(self::once())
            ->method('getStrategy')
            ->willReturn($defaultStrategy);
        $this->strategyProvider->expects(self::once())
            ->method('getStrategies')
            ->willReturn(['default' => $defaultStrategy, 'mix' => $mixingStrategy]);
        $this->strategyProvider->expects(self::exactly(3))
            ->method('setStrategy')
            ->withConsecutive(
                [self::identicalTo($defaultStrategy)],
                [self::identicalTo($mixingStrategy)],
                [self::identicalTo($defaultStrategy)]
            )
            ->willReturnCallback(function ($strategy) use (&$calls, $defaultStrategy, $mixingStrategy) {
                $strategyType = 'UNKNOWN';
                if ($strategy === $defaultStrategy) {
                    $strategyType = 'default';
                } elseif ($strategy === $mixingStrategy) {
                    $strategyType = 'mixing';
                }
                $calls[] = 'setStrategy - ' . $strategyType;
            });

        $this->innerWarmer->expects(self::exactly(2))
            ->method('warmUp')
            ->with($cacheDir)
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'warmUp';
            });

        $this->warmer->warmUp($cacheDir);

        self::assertEquals(
            [
                'setStrategy - default',
                'warmUp',
                'setStrategy - mixing',
                'warmUp',
                'setStrategy - default'
            ],
            $calls
        );
    }

    /**
     * @dataProvider optionalDataProvider
     */
    public function testIsOptional(bool $isOptional)
    {
        $this->innerWarmer->expects(self::once())
            ->method('isOptional')
            ->willReturn($isOptional);

        self::assertSame($isOptional, $this->warmer->isOptional());
    }

    public function optionalDataProvider(): array
    {
        return [
            'optional' => [true],
            'not optional' => [false],
        ];
    }
}
