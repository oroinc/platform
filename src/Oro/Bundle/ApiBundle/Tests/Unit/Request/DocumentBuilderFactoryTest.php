<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\DocumentBuilderFactory;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DocumentBuilderFactoryTest extends TestCase
{
    private ContainerInterface&MockObject $container;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function getDocumentBuilderFactory(array $documentBuilders): DocumentBuilderFactory
    {
        return new DocumentBuilderFactory(
            $documentBuilders,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    public function testShouldReturnDocumentBuilderIfItExistsForSpecificRequestType(): void
    {
        $factory = $this->getDocumentBuilderFactory([
            ['documentBuilder1', 'rest&json_api'],
            ['documentBuilder2', 'rest&!json_api'],
            ['documentBuilder3', null]
        ]);

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('documentBuilder2')
            ->willReturn($documentBuilder);

        self::assertSame(
            $documentBuilder,
            $factory->createDocumentBuilder(new RequestType(['rest']))
        );
    }

    public function testShouldReturnDefaultDocumentBuilderIfNoDocumentBuilderForSpecificRequestType(): void
    {
        $factory = $this->getDocumentBuilderFactory([
            ['documentBuilder1', 'rest&json_api'],
            ['documentBuilder2', 'rest&!json_api'],
            ['documentBuilder3', null]
        ]);

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('documentBuilder3')
            ->willReturn($documentBuilder);

        self::assertSame(
            $documentBuilder,
            $factory->createDocumentBuilder(new RequestType(['another']))
        );
    }

    public function testShouldThrowExceptionIfNoDocumentBuilderForSpecificRequestTypeAndNoDefaultDocumentBuilder(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find a document builder for the request "another".');

        $factory = $this->getDocumentBuilderFactory([
            ['documentBuilder1', 'rest&json_api']
        ]);

        $this->container->expects(self::never())
            ->method('get');

        $factory->createDocumentBuilder(new RequestType(['another']));
    }
}
