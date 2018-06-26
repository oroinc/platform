<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Provider\ChainSearchProvider;

class ChainSearchProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testAddProvider()
    {
        $provider = new SearchProviderStub(['test']);

        $searchProvider = new ChainSearchProvider();
        $searchProvider->addProvider($provider);

        $this->assertSame(['test'], $searchProvider->getData(''));
    }

    public function testSupportsTrue()
    {
        $provider = new SearchProviderStub(['test']);

        $searchProvider = new ChainSearchProvider();
        $searchProvider->addProvider($provider);

        $this->assertTrue($searchProvider->supports(''));
    }

    public function testSupportsFalse()
    {
        $searchProvider = new ChainSearchProvider();

        $this->assertFalse($searchProvider->supports(''));
    }

    public function testGetData()
    {
        $firstProvider = new SearchProviderStub(['test1']);
        $secondProvider = new SearchProviderStub(['test2']);
        $thirdProvider = new SearchProviderStub(['test3'], false);

        $searchProvider = new ChainSearchProvider();
        $searchProvider->addProvider($firstProvider);
        $searchProvider->addProvider($secondProvider);
        $searchProvider->addProvider($thirdProvider);

        $this->assertSame(['test1', 'test2'], $searchProvider->getData(''));
    }

    public function testGetDataEmpty()
    {
        $searchProvider = new ChainSearchProvider();

        $this->assertSame([], $searchProvider->getData(''));
    }
}
