<?php
namespace Oro\Bundle\DistributionBundle\Entity;

class PackageRequirement
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $installed;

    /**
     * @param string $name
     * @param bool $isInstalled
     */
    public function __construct($name, $isInstalled)
    {
        $this->name = $name;
        $this->installed = $isInstalled;
    }

    /**
     * @return boolean
     */
    public function isInstalled()
    {
        return $this->installed;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
            'installed' => $this->installed
        ];
    }
}
