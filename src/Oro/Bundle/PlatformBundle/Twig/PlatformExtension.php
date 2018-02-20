<?php

namespace Oro\Bundle\PlatformBundle\Twig;

use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PlatformExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_platform';

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
     * @return VersionHelper
     */
    protected function getVersionHelper()
    {
        return $this->container->get('oro_platform.composer.version_helper');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_version', [$this, 'getVersion'])
        ];
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->getVersionHelper()->getVersion();
    }

    /**
     * @return string The extension name
     */
    public function getName()
    {
        return self::EXTENSION_NAME;
    }
}
