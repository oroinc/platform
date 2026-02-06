<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

use Psr\Container\ContainerInterface;

/**
 * Provides a way to get {@see TypeFormatterInterface} for specified format type and data type.
 */
class FormatterProvider
{
    public const FORMAT_TYPE = 'formatType';

    private $formattersByType = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $typeFormatters = []
    ) {
    }

    public function getFormatterFor(string $formatType, string $dataType): ?TypeFormatterInterface
    {
        if (isset($this->formattersByType[$formatType][$dataType])) {
            return $this->formattersByType[$formatType][$dataType];
        }

        if (!isset($this->typeFormatters[$formatType][$dataType])) {
            return null;
        }

        $formatter = $this->container->get($this->typeFormatters[$formatType][$dataType]);
        $this->formattersByType[$formatType][$dataType] = $formatter;

        return $formatter;
    }
}
