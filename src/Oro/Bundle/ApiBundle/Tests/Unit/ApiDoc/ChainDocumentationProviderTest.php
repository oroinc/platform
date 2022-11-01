<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\ChainDocumentationProvider;
use Oro\Bundle\ApiBundle\ApiDoc\DocumentationProviderInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class ChainDocumentationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DocumentationProviderInterface */
    private $provider1;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DocumentationProviderInterface */
    private $provider2;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DocumentationProviderInterface */
    private $provider3;

    /** @var ChainDocumentationProvider */
    private $chainProvider;

    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(DocumentationProviderInterface::class);
        $this->provider2 = $this->createMock(DocumentationProviderInterface::class);
        $this->provider3 = $this->createMock(DocumentationProviderInterface::class);

        $container = TestContainerBuilder::create()
            ->add('provider1', $this->provider1)
            ->add('provider2', $this->provider2)
            ->add('provider3', $this->provider3)
            ->getContainer($this);

        $this->chainProvider = new ChainDocumentationProvider(
            [['provider1', null], ['provider2', null], ['provider3', 'rest']],
            $container,
            new RequestExpressionMatcher()
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
        $this->provider3->expects(self::never())
            ->method('getDocumentation');

        self::assertEquals(
            'provider1 documentation' . "\n\n" . 'provider2 documentation',
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
        $this->provider3->expects(self::never())
            ->method('getDocumentation');

        self::assertEquals(
            'provider2 documentation',
            $this->chainProvider->getDocumentation($requestType)
        );
    }
}
