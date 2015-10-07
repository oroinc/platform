<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

class FormatterProvider
{
    const FORMATTER_PROVIDER = 'formatter_provider';
    const FORMAT_TYPE_PREFIX = 'format_type_';

    protected $formatterIds       = [];
    protected $defaultsFormatters = [];
    protected $formatters         = [];
    protected $formattersByType   = [];
    protected $container;

    /**
     * @param ContainerInterface $container
     * @param array              $formatterIds
     * @param array              $defaultsFormatters
     */
    public function __construct(ContainerInterface $container, array $formatterIds = [], $defaultsFormatters = [])
    {
        $this->container          = $container;
        $this->formatterIds       = $formatterIds;
        $this->defaultsFormatters = $defaultsFormatters;
    }

    /**
     * @param string $type
     * @return null|TypeFormatterInterface
     */
    public function getFormatterFor($type)
    {
        $formatter = null;
        if (isset($this->formattersByType[$type])) {
            return $this->formattersByType[$type];
        }

        if (isset($this->defaultsFormatters[$type])) {
            $formatter                     = $this->getFormatterService($this->defaultsFormatters[$type]);
            $this->formattersByType[$type] = $formatter;
        }

        if (null === $formatter) {
            $message = sprintf('No available formatters for %s type.', $type);
            throw new InvalidArgumentException($message);
        }

        return $formatter;
    }

    /**
     * @param string $alias
     * @return null|TypeFormatterInterface
     */
    public function getFormatter($alias)
    {
        $formatter = null;
        if (isset($this->formatters[$alias])) {
            return $this->formatters[$alias];
        }
        if (isset($this->formatterIds[$alias])) {
            $formatter                = $this->getFormatterService($this->formatterIds[$alias]);
            $this->formatters[$alias] = $formatter;
        }

        if (null === $formatter) {
            $message = sprintf('The formatter alias "%s" is not registered with the provider.', $alias);
            throw new InvalidArgumentException($message);
        }

        return $formatter;
    }

    /**
     * @param string $formatterId
     * @return TypeFormatterInterface
     */
    protected function getFormatterService($formatterId)
    {
        if (!$this->container->has($formatterId)) {
            $message = sprintf('The formatter "%s" is not registered with the container.', $formatterId);
            throw new InvalidArgumentException($message);
        }

        return $this->container->get($formatterId);
    }
}
