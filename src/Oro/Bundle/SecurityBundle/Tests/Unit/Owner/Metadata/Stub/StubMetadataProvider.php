<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata\Stub;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\AbstractMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

class StubMetadataProvider extends AbstractMetadataProvider
{
    /**
     * {@inheritDoc}
     */
    protected function setAccessLevelClasses(array $owningEntityNames, EntityClassResolver $entityClassResolver = null)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function getNoOwnershipMetadata()
    {
        return new OwnershipMetadata();
    }

    /**
     * {@inheritDoc}
     */
    protected function getOwnershipMetadata(ConfigInterface $config)
    {
        return $this->noOwnershipMetadata;
    }

    /**
     * {@inheritDoc}
     */
    public function supports()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getBasicLevelClass()
    {
        return 'basicLevelClass';
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalLevelClass($deep = false)
    {
        return 'localLevelClass';
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobalLevelClass()
    {
        return 'globalLevelClass';
    }

    /**
     * {@inheritDoc}
     */
    public function getSystemLevelClass()
    {
        return 'systemLevelClass';
    }
}
