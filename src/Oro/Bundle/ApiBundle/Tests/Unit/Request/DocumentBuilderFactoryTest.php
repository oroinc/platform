<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\DocumentBuilderFactory;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DocumentBuilderFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @param array $documentBuilders
     *
     * @return DocumentBuilderFactory
     */
    private function getDocumentBuilderFactory(array $documentBuilders)
    {
        return new DocumentBuilderFactory(
            $documentBuilders,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    public function testShouldReturnDocumentBuilderIfItExistsForSpecificRequestType()
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

    public function testShouldReturnDefaultDocumentBuilderIfNoDocumentBuilderForSpecificRequestType()
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

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot find a document builder for the request "another".
     */
    public function testShouldThrowExceptionIfNoDocumentBuilderForSpecificRequestTypeAndNoDefaultDocumentBuilder()
    {
        $factory = $this->getDocumentBuilderFactory([
            ['documentBuilder1', 'rest&json_api']
        ]);

        $this->container->expects(self::never())
            ->method('get');

        $factory->createDocumentBuilder(new RequestType(['another']));
    }
}
