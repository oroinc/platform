<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

class FormatterProvider
{
    const FORMATTER_PROVIDER = 'formatter_provider';
    const FORMAT_TYPE_PREFIX = 'format_type_';

    /** @var array [{formatter_alias} => {formatter_service_id}] */
    protected $formatterIds       = [];

    /** @var array [{data_type} => {formatter_service_id}] */
    protected $defaultsFormatters = [];

    /** @var TypeFormatterInterface[] */
    protected $formatters         = [];

    /** @var TypeFormatterInterface[] */
    protected $formattersByType   = [];

    /** @var ContainerInterface */
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
     *
     * @return TypeFormatterInterface
     */
    public function getFormatterFor($type)
    {
        if (isset($this->formattersByType[$type])) {
            return $this->formattersByType[$type];
        }

        if (isset($this->defaultsFormatters[$type])) {
            $formatter                     = $this->getFormatterService($this->defaultsFormatters[$type]);
            $this->formattersByType[$type] = $formatter;

            return $formatter;
        }
        throw new InvalidArgumentException(
            sprintf('No available formatters for "%s" type.', $type)
        );
    }

    /**
     * @param string $alias
     *
     * @return TypeFormatterInterface
     */
    public function getFormatterByAlias($alias)
    {
        if (isset($this->formatters[$alias])) {
            return $this->formatters[$alias];
        }
        if (isset($this->formatterIds[$alias])) {
            $formatter                = $this->getFormatterService($this->formatterIds[$alias]);
            $this->formatters[$alias] = $formatter;

            return $formatter;
        }
        throw new InvalidArgumentException(
            sprintf('The formatter is not found by "%s" alias.', $alias)
        );
    }

    /**
     * @param string $formatterId
     *
     * @return TypeFormatterInterface
     */
    protected function getFormatterService($formatterId)
    {
        if (!$this->container->has($formatterId)) {
            $message = sprintf('The formatter "%s" is not registered with the service container.', $formatterId);
            throw new InvalidArgumentException($message);
        }

        return $this->container->get($formatterId);
    }
}
