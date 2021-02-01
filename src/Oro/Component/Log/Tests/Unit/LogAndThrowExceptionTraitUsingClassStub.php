<?php
declare(strict_types=1);

namespace Oro\Component\Log\Tests\Unit;

use Oro\Component\Log\LogAndThrowExceptionTrait;
use Psr\Log\LoggerInterface;

/**
 * DO NOT INSERT, DELETE OR MERGE LINES, as the line numbers are used in the constants.
 */
class LogAndThrowExceptionTraitUsingClassStub
{
    use LogAndThrowExceptionTrait;

    public const THROW_ERROR_LINE_NUMBER = 35;
    public const THROW_CRITICAL_LINE_NUMBER = 46;

    private ?LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger)
    {
        /** @noinspection UnusedConstructorDependenciesInspection used by a trait */
        $this->logger = $logger;
    }

    public function xthrowErrorException(
        string $exceptionClassname,
        string $message,
        array $context = [],
        ?\Throwable $previous = null,
        int $code = 0
    ): void {
        // The number of the line below is used in the THROW_ERROR_LINE_NUMBER constant.
        $this->throwErrorException($exceptionClassname, $message, $context, $previous, $code);
    }

    public function xthrowCriticalException(
        string $exceptionClassname,
        string $message,
        array $context = [],
        ?\Throwable $previous = null,
        int $code = 0
    ): void {
        // The number of the line below is used in the THROW_CRITICAL_LINE_NUMBER constant.
        $this->throwCriticalException($exceptionClassname, $message, $context, $previous, $code);
    }
}
