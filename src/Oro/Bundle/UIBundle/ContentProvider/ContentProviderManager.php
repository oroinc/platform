<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides a functionality to enable/disable content providers
 * and to build a content using enabled or specified content providers.
 */
class ContentProviderManager implements ResetInterface
{
    /** @var string[] */
    private $providerNames;

    /** @var ContainerInterface */
    private $providerContainer;

    /** @var string[] */
    private $enabledProviderNames;

    /** @var string[] */
    private $initialEnabledProviderNames;

    /**
     * @param string[]           $providerNames
     * @param ContainerInterface $providerContainer
     * @param string[]           $enabledProviderNames
     */
    public function __construct(
        array $providerNames,
        ContainerInterface $providerContainer,
        array $enabledProviderNames
    ) {
        $this->providerNames = $providerNames;
        $this->providerContainer = $providerContainer;
        $this->enabledProviderNames = $this->initialEnabledProviderNames = $enabledProviderNames;
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->enabledProviderNames = $this->initialEnabledProviderNames;
    }

    /**
     * Gets names of all content providers.
     *
     * @return string[]
     */
    public function getContentProviderNames(): array
    {
        return $this->providerNames;
    }

    /**
     * Disables the given content provider.
     */
    public function disableContentProvider(string $name): void
    {
        $foundIndex = array_search($name, $this->enabledProviderNames, true);
        if (false !== $foundIndex) {
            unset($this->enabledProviderNames[$foundIndex]);
            $this->enabledProviderNames = array_values($this->enabledProviderNames);
        }
    }

    /**
     * Enables the given content provider.
     */
    public function enableContentProvider(string $name): void
    {
        if (!\in_array($name, $this->enabledProviderNames, true) && \in_array($name, $this->providerNames, true)) {
            $this->enabledProviderNames[] = $name;
        }
    }

    /**
     * Gets a content.
     *
     * @param string[]|null $names
     *
     * @return array [name => content, ...]
     */
    public function getContent(array $names = null): array
    {
        if (!$names) {
            $names = $this->enabledProviderNames;
        }

        $content = [];
        foreach ($this->providerNames as $name) {
            if (\in_array($name, $names, true)) {
                /** @var ContentProviderInterface $provider */
                $provider = $this->providerContainer->get($name);
                $content[$name] = $provider->getContent();
            }
        }

        return $content;
    }
}
