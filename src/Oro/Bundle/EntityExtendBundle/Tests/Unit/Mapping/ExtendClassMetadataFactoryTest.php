<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityExtendBundle\Mapping\ExtendClassMetadataFactory;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

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
        $this->cmf->setCache(new ArrayAdapter(0, false));
        $this->cmf->setMetadataFor(User::class, new ClassMetadata(User::class));

        $cacheSalt = '__CLASSMETADATA__';
        $this->assertTrue($this->cmf->getCacheDriver()->contains('Oro\Bundle\UserBundle\Entity\User' . $cacheSalt));
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
