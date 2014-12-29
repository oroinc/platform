<?php

namespace Oro\Bundle\PlatformBundle\Twig;

use Oro\Bundle\PlatformBundle\Composer\VersionHelper;

class PlatformExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_platform';

    /**
     * @var VersionHelper
     */
    protected $helper;

    /**
     * @param VersionHelper $helper
     */
    public function __construct(VersionHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'oro_version' => new \Twig_Function_Method(
                $this,
                'getVersion'
            )
        );
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->helper->getVersion();
    }

    /**
     * @return string The extension name
     */
    public function getName()
    {
        return self::EXTENSION_NAME;
    }
}
