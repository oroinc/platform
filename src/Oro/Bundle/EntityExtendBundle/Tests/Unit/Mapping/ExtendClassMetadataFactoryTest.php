<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Mapping;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityExtendBundle\Mapping\ExtendClassMetadataFactory;

class ExtendClassMetadataFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExtendClassMetadataFactory
     */
    private $cmf;

    protected function setUp(): void
    {
        parent::setUp();

        $driver = $this->createMock('Doctrine\Persistence\Mapping\Driver\MappingDriver');
        $metadata = $this->createMock('Doctrine\Persistence\Mapping\ClassMetadata');
        $this->cmf = new ExtendClassMetadataFactory($driver, $metadata);
    }

    public function testSetMetadataFor()
    {
        $cache = new ArrayCache();
        $this->cmf->setCacheDriver($cache);

        $metadata = new ClassMetadata('Oro\Bundle\UserBundle\Entity\User');
        $this->cmf->setMetadataFor(
            'Oro\Bundle\UserBundle\Entity\User',
            $metadata
        );

        $cacheSalt = '$CLASSMETADATA';
        $this->assertTrue(
            $this->cmf->getCacheDriver()->contains('Oro\Bundle\UserBundle\Entity\User' . $cacheSalt)
        );
    }

    public function testSetMetadataForWithoutCacheDriver()
    {
        $metadata = new ClassMetadata('Oro\Bundle\UserBundle\Entity\User');
        $this->cmf->setMetadataFor(
            'Oro\Bundle\UserBundle\Entity\User',
            $metadata
        );
    }
}
