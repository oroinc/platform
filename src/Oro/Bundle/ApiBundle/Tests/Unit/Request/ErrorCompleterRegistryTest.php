<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ErrorCompleterRegistryTest extends TestCase
{
    private ContainerInterface&MockObject $container;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function getErrorCompleterRegistry(array $errorCompleters): ErrorCompleterRegistry
    {
        return new ErrorCompleterRegistry(
            $errorCompleters,
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    public function testShouldReturnErrorCompleterIfItExistsForSpecificRequestType(): void
    {
        $registry = $this->getErrorCompleterRegistry([
            ['errorCompleter1', 'rest&json_api'],
            ['errorCompleter2', 'rest&!json_api'],
            ['errorCompleter3', null]
        ]);

        $errorCompleter = $this->createMock(ErrorCompleterInterface::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('errorCompleter2')
            ->willReturn($errorCompleter);

        self::assertSame(
            $errorCompleter,
            $registry->getErrorCompleter(new RequestType(['rest']))
        );
    }

    public function testShouldReturnDefaultErrorCompleterIfNoErrorCompleterForSpecificRequestType(): void
    {
        $registry = $this->getErrorCompleterRegistry([
            ['errorCompleter1', 'rest&json_api'],
            ['errorCompleter2', 'rest&!json_api'],
            ['errorCompleter3', null]
        ]);

        $errorCompleter = $this->createMock(ErrorCompleterInterface::class);
        $this->container->expects(self::once())
            ->method('get')
            ->with('errorCompleter3')
            ->willReturn($errorCompleter);

        self::assertSame(
            $errorCompleter,
            $registry->getErrorCompleter(new RequestType(['another']))
        );
    }

    public function testShouldThrowExceptionIfNoErrorCompleterForSpecificRequestTypeAndNoDefaultErrorCompleter(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find an error completer for the request "another".');

        $registry = $this->getErrorCompleterRegistry([
            ['errorCompleter1', 'rest&json_api']
        ]);

        $this->container->expects(self::never())
            ->method('get');

        $registry->getErrorCompleter(new RequestType(['another']));
    }
}
