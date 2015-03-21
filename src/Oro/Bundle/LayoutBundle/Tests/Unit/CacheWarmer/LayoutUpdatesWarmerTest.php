<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\CacheWarmer;

use Oro\Component\Layout\Extension\Theme\Loader\LoaderInterface;

use Oro\Bundle\LayoutBundle\CacheWarmer\LayoutUpdatesWarmer;

class LayoutUpdatesWarmerTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldBeOptional()
    {
        $loader = $this->getMock('Oro\Component\Layout\Extension\Theme\Loader\LoaderInterface');

        $this->assertTrue($this->getWarmer($loader)->isOptional());
    }

    public function testWithEmptyResources()
    {
        $loader = $this->getMock('Oro\Component\Layout\Extension\Theme\Loader\LoaderInterface');

        $loader->expects($this->never())->method('supports');
        $loader->expects($this->never())->method('load');

        $warmer = $this->getWarmer($loader);
        $warmer->warmUp('');
    }

    public function testWithUnsupportedResources()
    {
        $loader = $this->getMock('Oro\Component\Layout\Extension\Theme\Loader\LoaderInterface');

        $loader->expects($this->once())->method('supports')
            ->with('layout_update.yml')
            ->willReturn(false);
        $loader->expects($this->never())->method('load')
            ->with('layout_update.yml');

        $warmer = $this->getWarmer($loader, ['oro-black' => ['layout_update.yml']]);
        $warmer->warmUp('');
    }

    public function testShouldSkipUnsupportedResourceAndProceedAllThemes()
    {
        $loader = $this->getMock('Oro\Component\Layout\Extension\Theme\Loader\LoaderInterface');

        $loader->expects($this->exactly(2))->method('load');
        $loader->expects($this->exactly(3))->method('supports')
            ->willReturnMap(
                [
                    ['layout_update_unsupported.xml', false],
                    ['layout_update_supported.yml', true],
                    ['supported_update.php', true]
                ]
            );

        $warmer = $this->getWarmer(
            $loader,
            [
                'oro-black' => ['layout_update_unsupported.xml', 'layout_update_supported.yml'],
                'oro-gold'  => ['route_name' => ['supported_update.php']]
            ]
        );
        $warmer->warmUp('');
    }

    /**
     * @param LoaderInterface $loader
     * @param array           $resources
     *
     * @return LayoutUpdatesWarmer
     */
    protected function getWarmer(LoaderInterface $loader, array $resources = [])
    {
        return new LayoutUpdatesWarmer($resources, $loader);
    }
}
