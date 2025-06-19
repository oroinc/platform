<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ApiRequestHelper;
use PHPUnit\Framework\TestCase;

class ApiRequestHelperTest extends TestCase
{
    private ApiRequestHelper $apiRequestHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->apiRequestHelper = new ApiRequestHelper('^/api/(?!(rest|doc)($|/.*))');
    }

    /**
     * @dataProvider isApiRequestDataProvider
     */
    public function testIsApiRequest(string $pathinfo, bool $isApiRequest): void
    {
        self::assertSame(
            $isApiRequest,
            $this->apiRequestHelper->isApiRequest($pathinfo)
        );
    }

    public function isApiRequestDataProvider(): array
    {
        return [
            ['/product/view/1', false],
            ['/api/products/1', true]
        ];
    }
}
