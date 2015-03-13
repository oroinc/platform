<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\CacheWarmer;

use Oro\Bundle\LayoutBundle\CacheWarmer\LayoutUpdatesWarmer;
use Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface;
use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactoryInterface;

class LayoutUpdatesWarmerTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldBeOptional()
    {
        $factory = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactoryInterface');
        $loader  = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface');

        $this->assertTrue($this->getWarmer($factory, $loader)->isOptional());
    }

    public function testWithEmptyResources()
    {
        $factory = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactoryInterface');
        $loader  = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface');

        $loader->expects($this->never())->method('supports');
        $loader->expects($this->never())->method('load');

        $factory->expects($this->never())->method('create');

        $warmer = $this->getWarmer($factory, $loader);
        $warmer->warmUp('');
    }

    public function testWithUnsupportedResources()
    {
        $factory = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactoryInterface');
        $loader  = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface');

        $resource = $this->getResourceMock();

        $loader->expects($this->once())->method('supports')->willReturn(false);
        $loader->expects($this->never())->method('load')->with($this->identicalTo($resource));

        $factory->expects($this->once())->method('create')->willReturn($resource);

        $warmer = $this->getWarmer($factory, $loader, ['oro-black' => ['layout_update.yml']]);
        $warmer->warmUp('');
    }

    public function testShouldSkipUnsupportedResourceAndProceedAllThemes()
    {
        $factory = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactoryInterface');
        $loader  = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface');

        $resourceUnsupported  = $this->getResourceMock();
        $resourceYmlSupported = $this->getResourceMock();
        $resourcePhpSupported = $this->getResourceMock();

        $loader->expects($this->exactly(2))->method('load');
        $loader->expects($this->exactly(3))->method('supports')
            ->willReturnMap(
                [
                    [$resourceUnsupported, false],
                    [$resourceYmlSupported, true],
                    [$resourcePhpSupported, true]
                ]
            );

        $factory->expects($this->exactly(3))->method('create')->willReturnMap(
            [
                ['layout_update_unsupported.xml', $resourceUnsupported],
                ['layout_update_supported.yml', $resourceYmlSupported],
                ['supported_update.php', $resourcePhpSupported]
            ]
        );

        $warmer = $this->getWarmer(
            $factory,
            $loader,
            [
                'oro-black' => ['layout_update_unsupported.xml', 'layout_update_supported.yml'],
                'oro-gold'  => ['route_name' => ['supported_update.php']]
            ]
        );
        $warmer->warmUp('');
    }

    /**
     * @param ResourceFactoryInterface $factory
     * @param LoaderInterface          $loader
     * @param array                    $resources
     *
     * @return LayoutUpdatesWarmer
     */
    protected function getWarmer(ResourceFactoryInterface $factory, LoaderInterface $loader, array $resources = [])
    {
        return new LayoutUpdatesWarmer($resources, $factory, $loader);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getResourceMock()
    {
        return $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\FileResource', [], [], '', false);
    }
}
