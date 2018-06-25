<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\ChainDocumentationProvider;
use Oro\Bundle\ApiBundle\ApiDoc\DocumentationProviderInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ChainDocumentationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DocumentationProviderInterface */
    private $provider1;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DocumentationProviderInterface */
    private $provider2;

    /** @var ChainDocumentationProvider */
    private $chainProvider;

    protected function setUp()
    {
        $this->provider1 = $this->createMock(DocumentationProviderInterface::class);
        $this->provider2 = $this->createMock(DocumentationProviderInterface::class);

        $this->chainProvider = new ChainDocumentationProvider(
            [$this->provider1, $this->provider2]
        );
    }

    public function testGetDocumentation()
    {
        $requestType = new RequestType(['test']);

        $this->provider1->expects(self::once())
            ->method('getDocumentation')
            ->willReturn('provider1 documentation');
        $this->provider2->expects(self::once())
            ->method('getDocumentation')
            ->willReturn('provider2 documentation');

        self::assertEquals(
            'provider1 documentation' . "\n" . 'provider2 documentation',
            $this->chainProvider->getDocumentation($requestType)
        );
    }

    public function testGetDocumentationWhenSomeProviderDoesNotReturnDocumentation()
    {
        $requestType = new RequestType(['test']);

        $this->provider1->expects(self::once())
            ->method('getDocumentation')
            ->willReturn(null);
        $this->provider2->expects(self::once())
            ->method('getDocumentation')
            ->willReturn('provider2 documentation');

        self::assertEquals(
            'provider2 documentation',
            $this->chainProvider->getDocumentation($requestType)
        );
    }
}
