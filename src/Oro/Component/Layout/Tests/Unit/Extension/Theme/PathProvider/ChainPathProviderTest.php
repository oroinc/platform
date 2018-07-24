<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\PathProvider;

use Oro\Component\Layout\Extension\Theme\PathProvider\ChainPathProvider;

class ChainPathProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainPathProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new ChainPathProvider();
    }

    protected function tearDown()
    {
        unset($this->provider);
    }

    public function testAddProviderUsePriorityForSorting()
    {
        $provider1 = $this->createMock('Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface');
        $provider2 = $this->createMock('Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface');
        $provider3 = $this->createMock('Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface');
        $this->provider->addProvider($provider1, 100);
        $this->provider->addProvider($provider2, -10);
        $this->provider->addProvider($provider3);

        $ref    = new \ReflectionClass($this->provider);
        $method = $ref->getMethod('getProviders');
        $method->setAccessible(true);

        $this->assertSame([$provider1, $provider3, $provider2], $method->invoke($this->provider));
    }

    public function testSetContext()
    {
        $context = $this->createMock('Oro\Component\Layout\ContextInterface');

        $provider1 = $this
            ->createMock('Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface');
        $provider2 = $this
            ->createMock('Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\StubContextAwarePathProvider');
        $provider2->expects($this->once())
            ->method('setContext')
            ->with($this->identicalTo($context));

        $this->provider->addProvider($provider1);
        $this->provider->addProvider($provider2);
        $this->provider->setContext($context);
    }

    public function testGetPaths()
    {
        $provider1 = $this->createMock('Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface');
        $provider2 = $this->createMock('Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface');
        $provider3 = $this->createMock('Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface');
        $this->provider->addProvider($provider1, 100);
        $this->provider->addProvider($provider2, 0);
        $this->provider->addProvider($provider3, -100);

        $provider1->expects($this->once())
            ->method('getPaths')
            ->will(
                $this->returnCallback(
                    function (array $existingPaths) {
                        $existingPaths[] = 'testPath1';
                        $existingPaths[] = 'testPath2/testSubPath';

                        return $existingPaths;
                    }
                )
            );
        $provider2->expects($this->once())
            ->method('getPaths')
            ->will(
                $this->returnCallback(
                    function (array $existingPaths) {
                        $existingPaths[] = 'testPath1';

                        return $existingPaths;
                    }
                )
            );
        $provider3->expects($this->once())
            ->method('getPaths')
            ->will(
                $this->returnCallback(
                    function (array $existingPaths) {
                        $existingPaths[] = 'testPath1/testSubPath';

                        return $existingPaths;
                    }
                )
            );

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
