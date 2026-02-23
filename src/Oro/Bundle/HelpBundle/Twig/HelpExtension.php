<?php

namespace Oro\Bundle\HelpBundle\Twig;

use Oro\Bundle\HelpBundle\Provider\HelpLinkProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve the online help URL:
 *   - get_help_link
 */
class HelpExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('get_help_link', [$this, 'getHelpLinkUrl'])
        ];
    }

    /**
     * @return string
     */
    public function getHelpLinkUrl()
    {
        return $this->getHelpLinkProvider()->getHelpLinkUrl();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            HelpLinkProvider::class
        ];
    }

    private function getHelpLinkProvider(): HelpLinkProvider
    {
        return $this->container->get(HelpLinkProvider::class);
    }
}
