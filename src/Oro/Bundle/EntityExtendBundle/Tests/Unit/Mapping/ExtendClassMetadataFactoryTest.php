<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Mapping;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityExtendBundle\Mapping\ExtendClassMetadataFactory;
use Oro\Bundle\UserBundle\Entity\User;

class ExtendClassMetadataFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtendClassMetadataFactory */
    private $cmf;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cmf = new ExtendClassMetadataFactory();
    }

    public function testSetMetadataFor()
    {
        $cache = new ArrayCache();
        $this->cmf->setCacheDriver($cache);

        $metadata = new ClassMetadata(User::class);
        $this->cmf->setMetadataFor(
            User::class,
            $metadata
        );

        $cacheSalt = '$CLASSMETADATA';
        $this->assertTrue(
            $this->cmf->getCacheDriver()->contains(User::class . $cacheSalt)
        );
    }

    public function testSetMetadataForWithoutCacheDriver()
    {
        $metadata = new ClassMetadata(User::class);
        $this->cmf->setMetadataFor(
            User::class,
            $metadata
        );
    }
}
