<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * The content provider that is intended to be injected in TWIG.
 */
class TwigContentProviderManager implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
        /** @var ContentProviderManager $contentProviderManager */
        $contentProviderManager = $this->container->get(ContentProviderManager::class);

        return $contentProviderManager->getContent($names);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return [ContentProviderManager::class];
    }
}
