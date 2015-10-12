<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

class FormatterProvider
{
    const FORMAT_TYPE = 'format_type';

    /** @var array [{formatter_alias} => {formatter_service_id}] */
    protected $formatterIds = [];

    /** @var array [{format_type} => [{data_type} => {formatter_service_id}]] */
    protected $formatTypes = [];

    /** @var TypeFormatterInterface[] */
    protected $formatters = [];

    /** @var TypeFormatterInterface[] */
    protected $formattersByType = [];

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     * @param array              $formatterIds
     * @param array              $formatTypes
     */
    public function __construct(ContainerInterface $container, array $formatterIds = [], $formatTypes = [])
    {
        $this->container    = $container;
        $this->formatterIds = $formatterIds;
        $this->formatTypes  = $formatTypes;
    }

    /**
     * @param string $formatType
     * @param string $dataType
     *
     * @return TypeFormatterInterface
     */
    public function getFormatterFor($formatType, $dataType)
    {
        if (isset($this->formattersByType[$formatType][$dataType])) {
            return $this->formattersByType[$formatType][$dataType];
        }

        if (isset($this->formatTypes[$formatType][$dataType])) {
            $formatter                                      = $this->getFormatterService(
                $this->formatTypes[$formatType][$dataType]
            );
            $this->formattersByType[$formatType][$dataType] = $formatter;

            return $formatter;
        }

        throw new InvalidArgumentException(
            sprintf('No available formatters for "%s" format_type and "%s" data_type.', $formatType, $dataType)
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
