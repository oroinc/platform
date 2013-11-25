<?php
namespace Oro\Bundle\DistributionBundle\Entity;

class PackageUpdate 
{
    /**
     * @var string
     */
    protected $packageName;
    /**
     * @var string
     */
    protected $currentVersionString;
    /**
     * @var string
     */
    protected $upToDateVersionString;

    /**
     * @param string $packageName
     * @param string $currentVersionString
     * @param string $upToDateVersionString
     */
    public function __construct($packageName, $currentVersionString, $upToDateVersionString)
    {
        $this->packageName = $packageName;
        $this->currentVersionString = $currentVersionString;
        $this->upToDateVersionString = $upToDateVersionString;
    }

    /**
     * @return string
     */
    public function getCurrentVersionString()
    {
        return $this->currentVersionString;
    }

    /**
     * @return string
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * @return string
     */
    public function getUpToDateVersionString()
    {
        return $this->upToDateVersionString;
    }


} 