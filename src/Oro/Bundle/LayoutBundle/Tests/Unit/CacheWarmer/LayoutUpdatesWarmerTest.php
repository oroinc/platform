<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\CacheWarmer;

use Oro\Component\Layout\Extension\Theme\Loader\LayoutUpdateLoaderInterface;

use Oro\Bundle\LayoutBundle\CacheWarmer\LayoutUpdatesWarmer;

class LayoutUpdatesWarmerTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldBeOptional()
    {
        $loader = $this->getMock('Oro\Component\Layout\Extension\Theme\Loader\LayoutUpdateLoaderInterface');

        $this->assertTrue($this->getWarmer($loader)->isOptional());
    }

    public function testWithEmptyResources()
    {
        $loader = $this->getMock('Oro\Component\Layout\Extension\Theme\Loader\LayoutUpdateLoaderInterface');

        $loader->expects($this->never())->method('load');

        $warmer = $this->getWarmer($loader);
        $warmer->warmUp('');
    }

    public function testShouldProceedAllThemes()
    {
        $loader = $this->getMock('Oro\Component\Layout\Extension\Theme\Loader\LayoutUpdateLoaderInterface');

        $loader->expects($this->exactly(3))->method('load');
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
     * @param LayoutUpdateLoaderInterface $loader
     * @param array                       $resources
     *
     * @return LayoutUpdatesWarmer
     */
    protected function getWarmer(LayoutUpdateLoaderInterface $loader, array $resources = [])
    {
        return new LayoutUpdatesWarmer($resources, $loader);
    }
}
