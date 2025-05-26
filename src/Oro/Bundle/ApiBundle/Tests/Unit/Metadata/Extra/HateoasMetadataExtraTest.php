<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata\Extra;

use Oro\Bundle\ApiBundle\Filter\QueryStringAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\Extra\HateoasMetadataExtra;
use PHPUnit\Framework\TestCase;

class HateoasMetadataExtraTest extends TestCase
{
    public function testGetName(): void
    {
        $extra = new HateoasMetadataExtra($this->createMock(QueryStringAccessorInterface::class));
        self::assertEquals(HateoasMetadataExtra::NAME, $extra->getName());
    }

    public function testCacheKeyPart(): void
    {
        $extra = new HateoasMetadataExtra($this->createMock(QueryStringAccessorInterface::class));
        self::assertEquals(HateoasMetadataExtra::NAME, $extra->getCacheKeyPart());
    }

    public function testGetQueryStringAccessor(): void
    {
        $queryStringAccessor = $this->createMock(QueryStringAccessorInterface::class);
        $extra = new HateoasMetadataExtra($queryStringAccessor);
        self::assertSame($queryStringAccessor, $extra->getQueryStringAccessor());
    }
}
