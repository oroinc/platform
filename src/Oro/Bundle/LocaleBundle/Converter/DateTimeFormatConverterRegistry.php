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
    private array $converterNames;
    private ContainerInterface $converterContainer;
    /** @var DateTimeFormatConverterInterface[]|null [name => converter, ...] */
    private ?array $converters = null;
    private bool $allConvertersLoaded = false;

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
        $this->ensureConvertersInitialized();

        if (!\array_key_exists($name, $this->converters)) {
            throw new \LogicException(sprintf('Format converter with name "%s" is not exist', $name));
        }

        $converter = $this->converters[$name];
        if (null === $converter) {
            $converter = $this->converterContainer->get($name);
            $this->converters[$name] = $converter;
        }

        return $converter;
    }

    /**
     * Gets all format converters.
     *
     * @return DateTimeFormatConverterInterface[] [name => converter, ...]
     */
    public function getFormatConverters(): array
    {
        if (!$this->allConvertersLoaded) {
            $this->ensureConvertersInitialized();
            foreach ($this->converters as $name => $converter) {
                if (null === $converter) {
                    $this->converters[$name] = $this->converterContainer->get($name);
                }
            }
            $this->allConvertersLoaded = true;
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

    private function ensureConvertersInitialized(): void
    {
        if (null === $this->converters) {
            $this->converters = [];
            foreach ($this->converterNames as $name) {
                $this->converters[$name] = null;
            }
        }
    }
}
