<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Filter\QueryStringAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\HateoasMetadataExtra;

class HateoasMetadataExtraTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName()
    {
        $extra = new HateoasMetadataExtra($this->createMock(QueryStringAccessorInterface::class));
        self::assertEquals(HateoasMetadataExtra::NAME, $extra->getName());
    }

    public function testCacheKeyPart()
    {
        $extra = new HateoasMetadataExtra($this->createMock(QueryStringAccessorInterface::class));
        self::assertEquals(HateoasMetadataExtra::NAME, $extra->getCacheKeyPart());
    }

    public function testGetQueryStringAccessor()
    {
        $queryStringAccessor = $this->createMock(QueryStringAccessorInterface::class);
        $extra = new HateoasMetadataExtra($queryStringAccessor);
        self::assertSame($queryStringAccessor, $extra->getQueryStringAccessor());
    }
}
