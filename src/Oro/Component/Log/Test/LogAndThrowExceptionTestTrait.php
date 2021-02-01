<?php
declare(strict_types=1);

namespace Oro\Component\Log\Test;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Provides methods to simplify testing of classes using LogAndThrowExceptionTrait:
 * - expectThrowErrorException()
 * - expectThrowCriticalException()
 *
 * @see \Oro\Component\Log\LogAndThrowExceptionTrait
 */
trait LogAndThrowExceptionTestTrait
{
    /** @var LoggerInterface|MockObject  */
    private LoggerInterface $logger;

    /**
     * Do not forget to initialize $this->logger in your setUp() method:
     * <code>
     *     protected function setUp(): void
     *     {
     *         $this->logger = $this->createMock(LoggerInterface::class);
     *     }
     * </code>
     *
     * The $expectedPreviousException parameter accepts an exception instance, null, boolean false or classname string:
     * - use false (default value), if you need to make sure that there is no 'exception' in the context,
     *   or if you are asserting the 'exception' key value as part of the context data assertion;
     * - use an exception instance for strict comparison;
     * - use null to ignore 'exception' in the context even if it is present.
     *
     * If $expectedExceptionMessageIsRegExp is true, the $expectedExceptionMessage will be used with
     * expectExceptionMessageMatches() assertion (useful if message contains some unknown generated value),
     * and with expectExceptionMessage() assertion otherwise.
     *
     * @noinspection PhpTooManyParametersInspection
     * @param string $expectedExceptionClass
     * @param string $expectedExceptionMessage
     * @param string $expectedLoggerMessage
     * @param array $expectedLoggerContext
     * @param \Throwable|null|false $expectedPreviousException
     * @param string|null $expectedCalledIn
     * @param bool $expectedExceptionMessageIsRegExp
     */
    public function expectThrowErrorException(
        string $expectedExceptionClass,
        string $expectedExceptionMessage,
        string $expectedLoggerMessage,
        array $expectedLoggerContext,
        $expectedPreviousException = false,
        string $expectedCalledIn = null,
        bool $expectedExceptionMessageIsRegExp = false
    ): void {
        $this->expectException($expectedExceptionClass);
        if ($expectedExceptionMessageIsRegExp) {
            $this->expectExceptionMessageMatches($expectedExceptionMessage);
        } else {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }
        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                $expectedLoggerMessage,
                new LoggerContextConstraint(
                    $expectedLoggerContext,
                    $expectedPreviousException,
                    $expectedCalledIn
                )
            );
    }
}
