<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Compiler;

use Oro\Component\DependencyInjection\Compiler\CompilerPassLogger;
use Oro\Component\DependencyInjection\Tests\Unit\Fixtures\CompilerPass1;
use Oro\Component\DependencyInjection\Tests\Unit\Fixtures\DummyObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompilerPassLoggerTest extends TestCase
{
    private ContainerBuilder $container;
    private CompilerPassLogger $logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();

        $this->logger = new CompilerPassLogger(new CompilerPass1(), $this->container, 'test');
    }

    /**
     * @dataProvider logsAtAllLevelsDataProvider
     */
    public function testLogsAtAllLevels(string $formattedLevel, string $level, string $message): void
    {
        $this->logger->{$level}($message, ['user' => 'Bob']);
        $this->logger->log($level, $message, ['user' => 'Bob']);

        self::assertEquals(
            [
                CompilerPass1::class . ': [test] ' . $formattedLevel . $level . ' message with context: Bob',
                CompilerPass1::class . ': [test] ' . $formattedLevel . $level . ' message with context: Bob',
            ],
            $this->container->getCompiler()->getLog()
        );
    }

    public static function logsAtAllLevelsDataProvider(): array
    {
        return [
            LogLevel::EMERGENCY => ['[ERROR] ', LogLevel::EMERGENCY, 'emergency message with context: {user}'],
            LogLevel::ALERT => ['[ERROR] ', LogLevel::ALERT, 'alert message with context: {user}'],
            LogLevel::CRITICAL => ['[ERROR] ', LogLevel::CRITICAL, 'critical message with context: {user}'],
            LogLevel::ERROR => ['[ERROR] ', LogLevel::ERROR, 'error message with context: {user}'],
            LogLevel::WARNING => ['[WARNING] ', LogLevel::WARNING, 'warning message with context: {user}'],
            LogLevel::NOTICE => ['', LogLevel::NOTICE, 'notice message with context: {user}'],
            LogLevel::INFO => ['', LogLevel::INFO, 'info message with context: {user}'],
            LogLevel::DEBUG => ['', LogLevel::DEBUG, 'debug message with context: {user}'],
        ];
    }

    public function testContextReplacement(): void
    {
        $this->logger->info(
            '{Message {nothing} {user} {foo.bar} a}',
            ['user' => 'Bob', 'foo.bar' => 'Bar', 'buz' => 'Buz']
        );

        self::assertEquals(
            [
                CompilerPass1::class . ': [test] {Message {nothing} Bob Bar a}. buz: Buz'
            ],
            $this->container->getCompiler()->getLog()
        );
    }

    public function testObjectCastToString(): void
    {
        $this->logger->notice(new DummyObject('dummy'));

        self::assertEquals(
            [
                CompilerPass1::class . ': [test] dummy'
            ],
            $this->container->getCompiler()->getLog()
        );
    }

    public function testContextCanContainAnything(): void
    {
        $context = [
            'bool' => true,
            'null' => null,
            'string' => 'Foo',
            'int' => 0,
            'float' => 0.5,
            'object' => new DummyObject('dummy1'),
            'nested' => ['with object' => new DummyObject('dummy2')],
            'resource' => fopen('php://memory', 'r'),
        ];

        $this->logger->warning('some message.', $context);

        self::assertEquals(
            [
                CompilerPass1::class . ': [test] [WARNING] some message.'
                . ' bool: 1. null: . string: Foo. int: 0. float: 0.5.'
                . ' object: dummy1. nested: [array]. resource: [resource]'
            ],
            $this->container->getCompiler()->getLog()
        );
    }

    public function testContextExceptionKeyCanBeExceptionOrOtherValues(): void
    {
        $this->logger->warning('message', ['exception' => 'oops']);
        $this->logger->critical('Uncaught Exception!', ['exception' => new \LogicException('Fail')]);

        $logs = $this->container->getCompiler()->getLog();
        self::assertCount(2, $logs);
        self::assertEquals(CompilerPass1::class . ': [test] [WARNING] message. exception: oops', $logs[0]);
        self::assertStringStartsWith(
            CompilerPass1::class . ': [test] [ERROR] Uncaught Exception!. exception: LogicException: Fail in',
            $logs[1]
        );
    }
}
