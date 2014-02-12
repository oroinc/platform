<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Mapping;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityExtendBundle\Mapping\ExtendClassMetadataFactory;

class ExtendClassMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExtendClassMetadataFactory
     */
    private $cmf;

    public function setUp()
    {
        parent::setUp();

        $driver = $this->getMock('Doctrine\Common\Persistence\Mapping\Driver\MappingDriver');
        $metadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->cmf = new ExtendClassMetadataFactory($driver, $metadata);
    }

    public function testClearCache()
    {
        $this->assertNull($this->cmf->getCacheDriver());

        $cache = new ArrayCache();
        $this->cmf->setCacheDriver($cache);
        $this->assertSame($cache, $this->cmf->getCacheDriver());

        $this->cmf->clearCache();

        $this->assertAttributeSame(
            ['DoctrineNamespaceCacheKey[]' => 2],
            'data',
            $this->cmf->getCacheDriver()
        );
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
        $this->assertAttributeSame(
            [
                'DoctrineNamespaceCacheKey[]' => 1,
                '[Oro\Bundle\UserBundle\Entity\User'.$cacheSalt .'][1]' => $metadata
            ],
            'data',
            $this->cmf->getCacheDriver()
        );
    }
}
