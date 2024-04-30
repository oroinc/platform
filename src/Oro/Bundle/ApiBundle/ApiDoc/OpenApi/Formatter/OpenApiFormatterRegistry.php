<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Formatter;

use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Exception\RenderInvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * The registry that allows to get the OpenAPI formatter for a specific format.
 */
class OpenApiFormatterRegistry
{
    /** @var string[] */
    private array $formats;
    private ContainerInterface $formatters;

    public function __construct(array $formats, ContainerInterface $formatters)
    {
        $this->formats = $formats;
        $this->formatters = $formatters;
    }

    /**
     * @return string[]
     */
    public function getFormats(): array
    {
        return $this->formats;
    }

    /**
     * @throws RenderInvalidArgumentException If a formatter for the given format does not exist
     */
    public function getFormatter(string $format): OpenApiFormatterInterface
    {
        if (!$this->formatters->has($format)) {
            throw new RenderInvalidArgumentException(sprintf('The format "%s" is not supported.', $format));
        }

        return $this->formatters->get($format);
    }
}
