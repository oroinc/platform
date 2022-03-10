<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

class ErrorCompleterRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

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

    public function testShouldReturnErrorCompleterIfItExistsForSpecificRequestType()
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

    public function testShouldReturnDefaultErrorCompleterIfNoErrorCompleterForSpecificRequestType()
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

    public function testShouldThrowExceptionIfNoErrorCompleterForSpecificRequestTypeAndNoDefaultErrorCompleter()
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
