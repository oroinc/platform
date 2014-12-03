<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Doctrine\Common\Collections\ArrayCollection;

class DataStorageExtension
{
    /** @var ArrayCollection */
    protected $storage;

    /** @var DataStorageExtension */
    protected static $instance;

    public function __construct()
    {
        $this->storage = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param ArrayCollection $storage
     */
    public function setStorage(ArrayCollection $storage)
    {
        $this->storage = $storage;
    }

    public function cleanStorage()
    {
        $this->storage = new ArrayCollection();
    }
}
