<?php

namespace Oro\Bundle\HelpBundle\Twig;

use Oro\Bundle\HelpBundle\Provider\HelpLinkProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides "get_help_link" TWIG function.
 */
class HelpExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return HelpLinkProvider
     */
    private function getHelpLinkProvider()
    {
        return $this->container->get('oro_help.help_link_provider');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_help_link', [$this, 'getHelpLinkUrl'])
        ];
    }

    /**
     * Get help link
     *
     * @return bool
     */
    public function getHelpLinkUrl()
    {
        return $this->getHelpLinkProvider()->getHelpLinkUrl();
    }
}
