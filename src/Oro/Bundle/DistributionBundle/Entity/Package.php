<?php

namespace Oro\Bundle\DistributionBundle\Entity;


class Package
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var array
     */
    protected $bundles = [];

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param Bundle $bundle
     */
    public function addBundle(Bundle $bundle)
    {
        if (!in_array($bundle, $this->bundles)) {
            $this->bundles[] = $bundle;
        }
    }

    /**
     * @return array
     */
    public function getBundles()
    {
        return $this->bundles;
    }
}