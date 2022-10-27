<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\PathProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\Theme\PathProvider\ChainPathProvider;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\StubContextAwarePathProvider;
use Oro\Component\Testing\ReflectionUtil;

class ChainPathProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainPathProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new ChainPathProvider();
    }

    public function testAddProviderUsePriorityForSorting()
    {
        $provider1 = $this->createMock(PathProviderInterface::class);
        $provider2 = $this->createMock(PathProviderInterface::class);
        $provider3 = $this->createMock(PathProviderInterface::class);
        $this->provider->addProvider($provider1, 100);
        $this->provider->addProvider($provider2, -10);
        $this->provider->addProvider($provider3);

        $this->assertSame(
            [$provider1, $provider3, $provider2],
            ReflectionUtil::callMethod($this->provider, 'getProviders', [])
        );
    }

    public function testSetContext()
    {
        $context = $this->createMock(ContextInterface::class);

        $provider1 = $this->createMock(PathProviderInterface::class);
        $provider2 = $this->createMock(StubContextAwarePathProvider::class);
        $provider2->expects($this->once())
            ->method('setContext')
            ->with($this->identicalTo($context));

        $this->provider->addProvider($provider1);
        $this->provider->addProvider($provider2);
        $this->provider->setContext($context);
    }

    public function testGetPaths()
    {
        $provider1 = $this->createMock(PathProviderInterface::class);
        $provider2 = $this->createMock(PathProviderInterface::class);
        $provider3 = $this->createMock(PathProviderInterface::class);
        $this->provider->addProvider($provider1, 100);
        $this->provider->addProvider($provider2, 0);
        $this->provider->addProvider($provider3, -100);

        $provider1->expects($this->once())
            ->method('getPaths')
            ->willReturnCallback(function (array $existingPaths) {
                $existingPaths[] = 'testPath1';
                $existingPaths[] = 'testPath2/testSubPath';

                return $existingPaths;
            });
        $provider2->expects($this->once())
            ->method('getPaths')
            ->willReturnCallback(function (array $existingPaths) {
                $existingPaths[] = 'testPath1';

                return $existingPaths;
            });
        $provider3->expects($this->once())
            ->method('getPaths')
            ->willReturnCallback(function (array $existingPaths) {
                $existingPaths[] = 'testPath1/testSubPath';

                return $existingPaths;
            });

        $this->assertSame(
            [
                'path',
                'testPath1',
                'testPath2/testSubPath',
                'testPath1/testSubPath'
            ],
            array_values($this->provider->getPaths(['path']))
        );
    }
}
