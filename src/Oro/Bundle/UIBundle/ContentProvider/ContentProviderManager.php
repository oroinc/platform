<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

use Doctrine\Common\Collections\ArrayCollection;

class ContentProviderManager
{
    /**
     * @var ArrayCollection|ContentProviderInterface[]
     */
    protected $contentProviders;

    public function __construct()
    {
        $this->contentProviders = new ArrayCollection();
    }

    /**
     * Add content provider
     *
     * @param ContentProviderInterface $contentProvider
     * @param bool $enabled
     * @return ContentProviderManager
     */
    public function addContentProvider(ContentProviderInterface $contentProvider, $enabled = true)
    {
        $contentProvider->setEnabled($enabled);
        $this->contentProviders->set($contentProvider->getName(), $contentProvider);

        return $this;
    }

    /**
     * Check content provider availability by name.
     *
     * @param string $name
     * @return bool
     */
    public function hasContentProvider($name)
    {
        return $this->contentProviders->offsetExists($name);
    }

    /**
     * Get all content providers.
     *
     * @return ArrayCollection|ContentProviderInterface[]
     */
    public function getContentProviders()
    {
        return $this->contentProviders;
    }

    /**
     * Disable content provider by name.
     *
     * @param string $name
     * @return ContentProviderManager
     */
    public function disableContentProvider($name)
    {
        if ($this->hasContentProvider($name)) {
            $this->contentProviders->get($name)->setEnabled(false);
        }

        return $this;
    }

    /**
     * Enable content provider by name.
     *
     * @param string $name
     * @return ContentProviderManager
     */
    public function enableContentProvider($name)
    {
        if ($this->hasContentProvider($name)) {
            $this->contentProviders->get($name)->setEnabled(true);
        }

        return $this;
    }

    /**
     * Get enabled content providers.
     *
     * @return ArrayCollection|ContentProviderInterface[]
     */
    public function getEnabledContentProviders()
    {
        return $this->contentProviders->filter(
            function (ContentProviderInterface $provider) {
                return $provider->isEnabled();
            }
        );
    }

    /**
     * Get content providers by given keys.
     *
     * @param array $keys
     * @return ArrayCollection|ContentProviderInterface[]
     */
    public function getContentProvidersByKeys(array $keys)
    {
        return $this->contentProviders->filter(
            function (ContentProviderInterface $provider) use ($keys) {
                return in_array($provider->getName(), $keys);
            }
        );
    }

    /**
     * Get content.
     *
     * @param array|null $keys
     * @return array
     */
    public function getContent(array $keys = null)
    {
        if ($keys) {
            $providers = $this->getContentProvidersByKeys($keys);
        } else {
            $providers = $this->getEnabledContentProviders();
        }

        $content = array();
        /** @var ContentProviderInterface $provider */
        foreach ($providers as $provider) {
            $content[$provider->getName()] = $provider->getContent();
        }
        return $content;
    }
}
