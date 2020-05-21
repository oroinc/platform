<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\DataAuditBundle\Provider\AuditFieldTypeProvider;

class AuditFieldTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuditFieldTypeProvider */
    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new AuditFieldTypeProvider();
    }

    public function testDefaultType()
    {
        $metadata = $this->createMock('Doctrine\ORM\Mapping\ClassMetadata');
        $metadata->expects($this->once())->method('hasField')->willReturn(false);
        $metadata->expects($this->once())->method('hasAssociation')->willReturn(false);

        $this->assertEquals('string', $this->provider->getFieldType($metadata, 'field'));
    }

    public function testCollection()
    {
        $metadata = $this->createMock('Doctrine\ORM\Mapping\ClassMetadata');
        $metadata->expects($this->once())->method('hasField')->willReturn(false);
        $metadata->expects($this->once())->method('hasAssociation')->willReturn(true);

        $this->assertEquals('collection', $this->provider->getFieldType($metadata, 'field'));
    }

    public function testUnidirectionalCollection()
    {
        $metadata = $this->createMock('Doctrine\ORM\Mapping\ClassMetadata');
        $metadata->expects($this->once())->method('hasField')->willReturn(false);
        $metadata->expects($this->once())->method('hasAssociation')->willReturn(false);

        $this->assertEquals('collection', $this->provider->getFieldType($metadata, 'stdClass::field'));
    }

    public function testType()
    {
        $metadata = $this->createMock('Doctrine\ORM\Mapping\ClassMetadata');
        $metadata->expects($this->once())->method('getTypeOfField')->willReturn('integer');
        $metadata->expects($this->once())->method('hasField')->willReturn(true);
        $metadata->expects($this->never())->method('hasAssociation');

        $this->assertEquals('integer', $this->provider->getFieldType($metadata, 'field'));
    }

    public function testTypeByClass()
    {
        $metadata = $this->createMock('Doctrine\ORM\Mapping\ClassMetadata');
        $metadata->expects($this->once())->method('getTypeOfField')->willReturn(Type::getType('integer'));
        $metadata->expects($this->once())->method('hasField')->willReturn(true);
        $metadata->expects($this->never())->method('hasAssociation');

        $this->assertEquals('integer', $this->provider->getFieldType($metadata, 'field'));
    }
}
