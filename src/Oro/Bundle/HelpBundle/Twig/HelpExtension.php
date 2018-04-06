<?php

namespace Oro\Bundle\HelpBundle\Twig;

use Oro\Bundle\HelpBundle\Model\HelpLinkProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HelpExtension extends \Twig_Extension
{
    const NAME = 'oro_help';

    /** @var ContainerInterface */
    protected $container;

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
    protected function getHelpLinkProvider()
    {
        return $this->container->get('oro_help.model.help_link_provider');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_help_link', [$this, 'getHelpLinkUrl']),
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
