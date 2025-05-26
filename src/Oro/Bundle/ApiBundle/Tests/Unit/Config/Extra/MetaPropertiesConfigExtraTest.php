<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\MetaPropertiesConfigExtra;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MetaPropertiesConfigExtraTest extends TestCase
{
    private MetaPropertiesConfigExtra $extra;

    #[\Override]
    protected function setUp(): void
    {
        $this->extra = new MetaPropertiesConfigExtra();
    }

    public function testGetName(): void
    {
        self::assertEquals(MetaPropertiesConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable(): void
    {
        self::assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        $this->extra->addMetaProperty('prop1', 'string');
        $this->extra->addMetaProperty('prop2', 'string');
        self::assertEquals(
            'meta_properties:prop1,prop2',
            $this->extra->getCacheKeyPart()
        );
    }

    public function testCacheKeyPartWhenMetaPropertiesAreNotRequested(): void
    {
        self::assertEquals(
            'meta_properties:',
            $this->extra->getCacheKeyPart()
        );
    }

    public function testAddMetaPropertyWithoutType(): void
    {
        $this->extra->addMetaProperty('prop1', null);
        self::assertNull($this->extra->getTypeOfMetaProperty('prop1'));
    }

    public function testAddMetaPropertyWithType(): void
    {
        $this->extra->addMetaProperty('prop1', 'integer');
        self::assertEquals(
            'integer',
            $this->extra->getTypeOfMetaProperty('prop1')
        );
    }

    public function testGetTypeOfUnknownMetaProperty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "prop1" meta property does not exist.');

        $this->extra->getTypeOfMetaProperty('prop1');
    }

    public function testSetTypeOfMetaProperty(): void
    {
        $this->extra->addMetaProperty('prop1', 'string');
        $this->extra->setTypeOfMetaProperty('prop1', 'integer');
        self::assertEquals(
            'integer',
            $this->extra->getTypeOfMetaProperty('prop1')
        );
    }

    public function testSetTypeOfUnknownMetaProperty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "prop1" meta property does not exist.');

        $this->extra->setTypeOfMetaProperty('prop1', 'integer');
    }

    public function testGetMetaPropertyNames(): void
    {
        $this->extra->addMetaProperty('prop1', 'string');
        $this->extra->addMetaProperty('prop2', 'integer');
        self::assertEquals(
            ['prop1', 'prop2'],
            $this->extra->getMetaPropertyNames()
        );
    }

    public function testRemoveMetaProperty(): void
    {
        $this->extra->addMetaProperty('prop1', 'string');
        $this->extra->removeMetaProperty('prop1');
        self::assertEquals([], $this->extra->getMetaPropertyNames());
    }

    public function testRemoveUnknownMetaProperty(): void
    {
        $this->extra->addMetaProperty('prop1', 'string');
        $this->extra->removeMetaProperty('unknownProp');
        self::assertEquals(['prop1'], $this->extra->getMetaPropertyNames());
    }
}
