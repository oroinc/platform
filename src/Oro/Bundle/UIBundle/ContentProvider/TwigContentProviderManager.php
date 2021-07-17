<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * The content provider that is intended to be injected in TWIG.
 */
class TwigContentProviderManager implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $container;

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
        $contentProviderManager = $this->container->get('oro_ui.content_provider.manager');

        return $contentProviderManager->getContent($names);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_ui.content_provider.manager' => ContentProviderManager::class
        ];
    }
}
