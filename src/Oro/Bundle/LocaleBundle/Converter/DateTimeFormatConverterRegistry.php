<?php

namespace Oro\Bundle\LocaleBundle\Converter;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The registry of datetime format converters.
 */
class DateTimeFormatConverterRegistry implements ResetInterface
{
    /** @var string[] */
    private $converterNames;

    /** @var ContainerInterface */
    private $converterContainer;

    /** @var DateTimeFormatConverterInterface[]|null [name => converter, ...] */
    private $converters;

    /**
     * @param string[]           $converterNames
     * @param ContainerInterface $converterContainer
     */
    public function __construct(array $converterNames, ContainerInterface $converterContainer)
    {
        $this->converterNames = $converterNames;
        $this->converterContainer = $converterContainer;
    }

    /**
     * Gets a format converter by its name.
     *
     * @throws \LogicException if a format converter for the given name was not found
     */
    public function getFormatConverter(string $name): DateTimeFormatConverterInterface
    {
        if (!\in_array($name, $this->converterNames, true)) {
            throw new \LogicException(sprintf('Format converter with name "%s" is not exist', $name));
        }

        return $this->converterContainer->get($name);
    }

    /**
     * Gets all format converters.
     *
     * @return DateTimeFormatConverterInterface[] [name => converter, ...]
     */
    public function getFormatConverters(): array
    {
        if (null === $this->converters) {
            $this->converters = [];
            foreach ($this->converterNames as $name) {
                $this->converters[$name] = $this->converterContainer->get($name);
            }
        }

        return $this->converters;
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->converters = null;
    }
}
