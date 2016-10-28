<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class ConfigFile
{
    /**@var string */
    private $realPath;

    /**
     * @var BundleInterface
     */
    private $bundle;

    /**
     * @param string $realPath
     */
    public function __construct($realPath, BundleInterface $bundle)
    {
        $this->realPath = $realPath;
        $this->bundle = $bundle;
    }

    /**
     * @return string
     */
    public function getRealPath()
    {
        return $this->realPath;
    }

    /**
     * @return \SplFileInfo
     */
    public function getFileInfo()
    {
        return new \SplFileInfo($this->realPath);
    }

    /**
     * @return BundleInterface
     */
    public function getBundle()
    {
        return $this->bundle;
    }
}
