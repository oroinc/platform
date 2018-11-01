<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\MetaPropertiesConfigExtra;

class MetaPropertiesConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var MetaPropertiesConfigExtra */
    private $extra;

    protected function setUp()
    {
        $this->extra = new MetaPropertiesConfigExtra();
    }

    public function testGetName()
    {
        self::assertEquals(MetaPropertiesConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        self::assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->extra->addMetaProperty('prop1');
        $this->extra->addMetaProperty('prop2');
        self::assertEquals(
            'meta_properties:prop1,prop2',
            $this->extra->getCacheKeyPart()
        );
    }

    public function testCacheKeyPartWhenMetaPropertiesAreNotRequested()
    {
        self::assertEquals(
            'meta_properties:',
            $this->extra->getCacheKeyPart()
        );
    }

    public function testAddMetaPropertyWithoutType()
    {
        $this->extra->addMetaProperty('prop1');
        self::assertEquals(
            'string',
            $this->extra->getTypeOfMetaProperty('prop1')
        );
    }

    public function testAddMetaPropertyWithType()
    {
        $this->extra->addMetaProperty('prop1', 'integer');
        self::assertEquals(
            'integer',
            $this->extra->getTypeOfMetaProperty('prop1')
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "prop1" meta property does not exist.
     */
    public function testGetTypeOfUnknownMetaProperty()
    {
        $this->extra->getTypeOfMetaProperty('prop1');
    }

    public function testSetTypeOfMetaProperty()
    {
        $this->extra->addMetaProperty('prop1');
        $this->extra->setTypeOfMetaProperty('prop1', 'integer');
        self::assertEquals(
            'integer',
            $this->extra->getTypeOfMetaProperty('prop1')
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "prop1" meta property does not exist.
     */
    public function testSetTypeOfUnknownMetaProperty()
    {
        $this->extra->setTypeOfMetaProperty('prop1', 'integer');
    }

    public function testGetMetaPropertyNames()
    {
        $this->extra->addMetaProperty('prop1');
        $this->extra->addMetaProperty('prop2', 'integer');
        self::assertEquals(
            ['prop1', 'prop2'],
            $this->extra->getMetaPropertyNames()
        );
    }

    public function testRemoveMetaProperty()
    {
        $this->extra->addMetaProperty('prop1');
        $this->extra->removeMetaProperty('prop1');
        self::assertEquals([], $this->extra->getMetaPropertyNames());
    }

    public function testRemoveUnknownMetaProperty()
    {
        $this->extra->addMetaProperty('prop1');
        $this->extra->removeMetaProperty('unknownProp');
        self::assertEquals(['prop1'], $this->extra->getMetaPropertyNames());
    }
}
