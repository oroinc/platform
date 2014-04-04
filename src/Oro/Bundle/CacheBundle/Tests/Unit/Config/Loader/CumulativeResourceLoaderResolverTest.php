<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Config\Loader;

use Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoaderResolver;

class CumulativeResourceLoaderResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot resolve a loader for "Resources/config/test.blah
     */
    public function testResolveUnknown()
    {
        $resolver = new CumulativeResourceLoaderResolver();
        $resolver->resolve('Resources/config/test.blah');
    }

    public function testResolveYaml()
    {
        $resolver = new CumulativeResourceLoaderResolver();
        $loader = $resolver->resolve('Resources/config/test.yml');
        $this->assertInstanceOf('Oro\Bundle\CacheBundle\Config\Loader\YamlCumulativeFileLoader', $loader);
        $this->assertEquals('Resources/config/test.yml', $loader->getResource());
    }
}
