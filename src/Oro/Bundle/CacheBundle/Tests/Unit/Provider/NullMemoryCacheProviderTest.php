<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Provider\NullMemoryCacheProvider;

class NullMemoryCacheProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var NullMemoryCacheProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new NullMemoryCacheProvider();
    }

    public function testGetWithCallback(): void
    {
        $expectedData = 'sample_data';

        $this->assertEquals(
            $expectedData,
            $this->provider->get(
                [],
                static function () use ($expectedData) {
                    return $expectedData;
                }
            )
        );
    }

    public function testGetWithoutCallback(): void
    {
        $this->assertNull($this->provider->get('sample_key'));
    }
}
