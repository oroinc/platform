<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

class EntityNameResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $provider1;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $provider2;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    protected function setUp()
    {
        $this->provider1 = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface');
        $this->provider2 = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface');

        $this->entityNameResolver = new EntityNameResolver(
            'full',
            [
                'full'  => ['fallback' => 'short'],
                'short' => ['fallback' => null]
            ]
        );
        $this->entityNameResolver->addProvider($this->provider1);
        $this->entityNameResolver->addProvider($this->provider2, 1);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The unknown representation format "other".
     */
    public function testGetNameForUndefinedFormat()
    {
        $this->entityNameResolver->getName(new \stdClass(), 'other');
    }

    public function testGetNameForNullEntity()
    {
        $this->provider1->expects($this->never())
            ->method('getName');
        $this->provider2->expects($this->never())
            ->method('getName');

        $this->assertNull($this->entityNameResolver->getName(null, 'full'));
    }

    public function testGetNameWhenRequestedFormatImplementedByRegisteredProviders()
    {
        $entity   = new \stdClass();
        $format   = 'full';
        $locale   = 'en_US';
        $expected = 'EntityName';

        $this->provider1->expects($this->once())
            ->method('getName')
            ->with($format, $locale, $this->identicalTo($entity))
            ->willReturn($expected);

        $this->provider2->expects($this->once())
            ->method('getName')
            ->with($format, $locale, $this->identicalTo($entity))
            ->willReturn(false);

        $result = $this->entityNameResolver->getName($entity, $format, $locale);
        $this->assertEquals($expected, $result);
    }

    public function testGetNameWhenTheNameIsNull()
    {
        $entity = new \stdClass();
        $format = 'full';
        $locale = 'en_US';

        $this->provider1->expects($this->never())
            ->method('getName');

        $this->provider2->expects($this->once())
            ->method('getName')
            ->with($format, $locale, $this->identicalTo($entity))
            ->willReturn(null);

        $result = $this->entityNameResolver->getName($entity, $format, $locale);
        $this->assertNull($result);
    }

    public function testGetNameByFallbackFormat()
    {
        $entity   = new \stdClass();
        $format   = 'full';
        $locale   = 'en_US';
        $expected = 'EntityName';

        $this->provider1->expects($this->once())
            ->method('getName')
            ->with($format, $locale, $this->identicalTo($entity))
            ->willReturn(false);

        $this->provider2->expects($this->at(0))
            ->method('getName')
            ->with($format, $locale, $this->identicalTo($entity))
            ->willReturn(false);
        $this->provider2->expects($this->at(1))
            ->method('getName')
            ->with('short', $locale, $this->identicalTo($entity))
            ->willReturn($expected);

        $result = $this->entityNameResolver->getName($entity, $format, $locale);
        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The unknown representation format "other".
     */
    public function testGetNameDQLForUndefinedFormat()
    {
        $this->entityNameResolver->getNameDQL('Test\Entity', 'alias', 'other');
    }

    public function testGetNameDQLWhenRequestedFormatImplementedByRegisteredProviders()
    {
        $className = 'Test\Entity';
        $alias     = 'entity_alias';
        $format    = 'full';
        $locale    = 'en_US';
        $expected  = $alias . '.field';

        $this->provider1->expects($this->once())
            ->method('getNameDQL')
            ->with($format, $locale, $className, $alias)
            ->willReturn($expected);

        $this->provider2->expects($this->once())
            ->method('getNameDQL')
            ->with($format, $locale, $className, $alias)
            ->willReturn(false);

        $result = $this->entityNameResolver->getNameDQL($className, $alias, $format, $locale);
        $this->assertEquals($expected, $result);
    }

    public function testGetNameDQLByFallbackFormat()
    {
        $className = 'Test\Entity';
        $alias     = 'entity_alias';
        $format    = 'full';
        $locale    = 'en_US';
        $expected  = $alias . '.field';

        $this->provider1->expects($this->once())
            ->method('getNameDQL')
            ->with($format, $locale, $className, $alias)
            ->willReturn(false);

        $this->provider2->expects($this->at(0))
            ->method('getNameDQL')
            ->with($format, $locale, $className, $alias)
            ->willReturn(false);
        $this->provider2->expects($this->at(1))
            ->method('getNameDQL')
            ->with('short', $locale, $className, $alias)
            ->willReturn($expected);

        $result = $this->entityNameResolver->getNameDQL($className, $alias, $format, $locale);
        $this->assertEquals($expected, $result);
    }
}
