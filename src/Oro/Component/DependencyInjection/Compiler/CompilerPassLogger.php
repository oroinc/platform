<?php

namespace Oro\Component\DependencyInjection\Compiler;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * PSR-3 compliant logger that can be user in DIC compiler passes
 * to write log records via {@see ContainerBuilder::log}.
 */
class CompilerPassLogger extends AbstractLogger
{
    private const array FORMATTED_LEVEL_MAP = [
        LogLevel::EMERGENCY => '[ERROR] ',
        LogLevel::ALERT => '[ERROR] ',
        LogLevel::CRITICAL => '[ERROR] ',
        LogLevel::ERROR => '[ERROR] ',
        LogLevel::WARNING => '[WARNING] ',
        LogLevel::NOTICE => '',
        LogLevel::INFO => '',
        LogLevel::DEBUG => ''
    ];

    public function __construct(
        private readonly CompilerPassInterface $pass,
        private readonly ContainerBuilder $container,
        private readonly ?string $channel = null
    ) {
    }

    #[\Override]
    public function log($level, $message, array $context = [])
    {
        $this->container->log($this->pass, $this->format($level, $message, $context));
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function format(string $level, string $message, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $val) {
            $placeholder = "{{$key}}";
            if (null === $val || \is_scalar($val) || $val instanceof \Stringable) {
                $replacements[$placeholder] = $val;
            } elseif ($val instanceof \DateTimeInterface) {
                $replacements[$placeholder] = $val->format(\DateTimeInterface::RFC3339);
            } elseif (\is_object($val)) {
                $replacements[$placeholder] = '[object ' . $val::class . ']';
            } else {
                $replacements[$placeholder] = '[' . \gettype($val) . ']';
            }
            if (!str_contains($message, $placeholder)) {
                if (!str_ends_with($message, '.')) {
                    $message .= '.';
                }
                $message .= " $key: $placeholder";
            }
        }

        return
            ($this->channel ? "[$this->channel] " : '')
            . self::FORMATTED_LEVEL_MAP[$level]
            . strtr($message, $replacements);
    }
}
