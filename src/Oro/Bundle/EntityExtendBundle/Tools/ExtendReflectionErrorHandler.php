<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

/**
 * Helps to handle situation where extend entity is used before EntityExtendBundle initialization.
 * Should be used only with enabled debug.
 */
class ExtendReflectionErrorHandler
{
    private static bool $initialized = false;
    private static array $traces = [];

    private static function isInitialized(): bool
    {
        return self::$initialized;
    }

    public static function initialize(): void
    {
        self::$initialized = true;

        // register class loader with the highest priority
        spl_autoload_register([__CLASS__, 'dataCollector'], true, true);
    }

    public static function dataCollector(string $className): bool
    {
        // store traces only for entities
        if (str_contains($className, '\Entity\\')) {
            self::$traces[$className] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        // do not remove, it indicates that class loader was skipped
        return false;
    }

    public static function isSupported(\Exception $exception): bool
    {
        $firstTrace = $exception->getTrace()[0] ?? [];

        return $exception instanceof \ReflectionException
            && 'ReflectionProperty' === $firstTrace['class'] ?? ''
            && '__construct' === $firstTrace['function'] ?? '';
    }

    public static function createException(string $className, \Throwable $prevException): \Throwable
    {
        if (self::isInitialized()) {
            $message = "Extend entity $className autoloaded before initialization.";
        } else {
            $message = "Extend entity $className autoloaded before initialization. ".
                "For additional information run an application with debug true.";
        }

        return self::buildException($message, self::getTrace($className), $prevException);
    }

    public static function buildException(string $message, ?array $trace, ?\Throwable $prevException = null): \Throwable
    {
        $exception = new \ReflectionException($message, 0, $prevException);

        if ($trace) {
            $exceptionReflection = new \ReflectionObject($exception);

            while ($exceptionReflection->getParentClass() !== false) {
                $exceptionReflection = $exceptionReflection->getParentClass();
            }
            $traceReflection = $exceptionReflection->getProperty('trace');
            $traceReflection->setAccessible(true);
            $traceReflection->setValue($exception, $trace);
            $traceReflection->setAccessible(false);
        }

        return $exception;
    }

    public static function getTrace(string $className): ?array
    {
        return self::$traces[$className] ?? null;
    }
}
