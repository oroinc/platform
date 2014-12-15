<?php

namespace Oro\Bundle\MigrationBundle\Migration;

class MigrationState
{
    /** @var Migration */
    protected $migration;

    /** @var string */
    protected $bundleName;

    /** @var string */
    protected $version;

    /**
     * @var bool|null
     *  null - not executed
     *  true - success
     *  false = failure
     */
    protected $state;

    /**
     * @param Migration   $migration
     * @param string|null $bundleName
     * @param string|null $version
     */
    public function __construct(Migration $migration, $bundleName = null, $version = null)
    {
        $this->migration  = $migration;
        $this->bundleName = $bundleName;
        $this->version    = $version;
    }

    /**
     * @return Migration
     */
    public function getMigration()
    {
        return $this->migration;
    }

    /**
     * @return string
     */
    public function getBundleName()
    {
        return $this->bundleName;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return boolean
     */
    public function isSuccessful()
    {
        return $this->state === true;
    }

    /**
     * Marks a migration as successfully finished
     */
    public function setSuccessful()
    {
        $this->state = true;
    }

    /**
     * Marks a migration as failed
     */
    public function setFailed()
    {
        $this->state = false;
    }
}
