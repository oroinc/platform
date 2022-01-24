<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

class ResourceDocParserRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function getResourceDocParserRegistry(array $resourceDocParsers): ResourceDocParserRegistry
    {
        return new ResourceDocParserRegistry(
            $resourceDocParsers,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    public function testShouldReturnResourceDocParserIfItExistsForSpecificRequestType()
    {
        $registry = $this->getResourceDocParserRegistry([
            ['resourceDocParser1', 'rest&json_api'],
            ['resourceDocParser2', 'rest'],
            ['resourceDocParser3', null]
        ]);

        $resourceDocParser = $this->createMock(ResourceDocParserInterface::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('resourceDocParser2')
            ->willReturn($resourceDocParser);

        self::assertSame(
            $resourceDocParser,
            $registry->getParser(new RequestType(['rest']))
        );
    }

    public function testShouldReturnDefaultResourceDocParserIfNoParserForSpecificRequestType()
    {
        $registry = $this->getResourceDocParserRegistry([
            ['resourceDocParser1', 'rest&json_api'],
            ['resourceDocParser2', 'rest'],
            ['resourceDocParser3', null]
        ]);

        $resourceDocParser = $this->createMock(ResourceDocParserInterface::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('resourceDocParser3')
            ->willReturn($resourceDocParser);

        self::assertSame(
            $resourceDocParser,
            $registry->getParser(new RequestType(['another']))
        );
    }

    public function testShouldThrowExceptionIfNoResourceDocParserForSpecificRequestTypeAndNoDefaultParser()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find a resource documentation parser for the request "another".');

        $registry = $this->getResourceDocParserRegistry([
            ['resourceDocParser1', 'rest&json_api']
        ]);

        $this->container->expects(self::never())
            ->method('get');

        $registry->getParser(new RequestType(['another']));
    }
}
