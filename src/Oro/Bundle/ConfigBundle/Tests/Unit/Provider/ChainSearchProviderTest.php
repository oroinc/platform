<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Provider\ChainSearchProvider;

class ChainSearchProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testSupports()
    {
        $searchProvider = new ChainSearchProvider([]);

        $this->assertTrue($searchProvider->supports(''));
    }

    public function testGetData()
    {
        $firstProvider = new SearchProviderStub(['test1']);
        $secondProvider = new SearchProviderStub(['test2']);
        $thirdProvider = new SearchProviderStub(['test3'], false);

        $searchProvider = new ChainSearchProvider([$firstProvider, $secondProvider, $thirdProvider]);

        $this->assertSame(['test1', 'test2'], $searchProvider->getData(''));
    }

    public function testGetDataEmpty()
    {
        $searchProvider = new ChainSearchProvider([]);

        $this->assertSame([], $searchProvider->getData(''));
    }
}
