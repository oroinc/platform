<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ApiBundle\Request\DocumentBuilderFactory;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

class DocumentBuilderFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface */
    protected $container;

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

    public function testSouldReturnDocumentBuilderIfItExistsForSpecificRequestType()
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

    public function testSouldReturnDefaultDocumentBuilderIfNoDocumentBuilderForSpecificRequestType()
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
    public function testSouldThrowExceptionIfNoDocumentBuilderForSpecificRequestTypeAndNoDefaultDocumentBuilder()
    {
        $factory = $this->getDocumentBuilderFactory([
            ['documentBuilder1', 'rest&json_api']
        ]);

        $this->container->expects(self::never())
            ->method('get');

        $factory->createDocumentBuilder(new RequestType(['another']));
    }
}
