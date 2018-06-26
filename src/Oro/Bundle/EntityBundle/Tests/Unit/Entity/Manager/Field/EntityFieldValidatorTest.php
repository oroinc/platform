<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Entity\Manager\Field;

use Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldValidator;

class EntityFieldValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translation;

    /** @var EntityFieldValidator */
    protected $validator;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->setMethods(['getManager'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->translation = $this->createMock('Symfony\Component\Translation\TranslatorInterface');

        $this->validator = new EntityFieldValidator($this->registry, $this->translation);
    }

    public function testPositiveValidate()
    {
        $classMetadata = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects(self::once())
            ->method('hasField')
            ->willReturn(true);
        $classMetadata
            ->expects(self::never())
            ->method('hasAssociation');

        $objectManager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $objectManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->registry
            ->expects(self::once())
            ->method('getManager')
            ->willReturn($objectManager);

        $entity  = new \StdClass();
        $content = [
            'field1' => 'val1'
        ];

        $this->validator->validate($entity, $content);
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityHasFieldException
     * @expectedExceptionMessage oro.entity.controller.message.field_not_found
     */
    public function testValidateWithFieldException()
    {
        $classMetadata = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects(self::once())
            ->method('hasField')
            ->willReturn(false);
        $classMetadata
            ->expects(self::once())
            ->method('hasAssociation')
            ->willReturn(false);

        $objectManager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $objectManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->registry
            ->expects(self::once())
            ->method('getManager')
            ->willReturn($objectManager);

        $entity  = new \StdClass();
        $content = [
            'field1' => 'val1'
        ];

        $this->validator->validate($entity, $content);
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException
     * @expectedExceptionMessage oro.entity.controller.message.access_denied
     */
    public function testValidateWithAccessException()
    {
        $classMetadata = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects(self::once())
            ->method('hasField')
            ->willReturn(true);
        $classMetadata
            ->expects(self::never())
            ->method('hasAssociation');

        $objectManager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $objectManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->registry
            ->expects(self::once())
            ->method('getManager')
            ->willReturn($objectManager);

        $entity  = new \StdClass();
        $content = [
            'createdAt' => 'val1'
        ];

        $this->validator->validate($entity, $content);
    }

    public function testPositiveCustomValidate()
    {
        $classMetadata = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects(self::once())
            ->method('hasField')
            ->willReturn(true);
        $classMetadata
            ->expects(self::never())
            ->method('hasAssociation');

        $objectManager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $objectManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->registry
            ->expects(self::once())
            ->method('getManager')
            ->willReturn($objectManager);

        $entity  = new \StdClass();
        $content = [
            'field1' => 'val1'
        ];

        $customGridFieldValidator = $this
            ->createMock('Oro\Bundle\EntityBundle\Entity\Manager\Field\CustomGridFieldValidatorInterface');
        $customGridFieldValidator
            ->expects(self::once())
            ->method('hasAccessEditField')
            ->willReturn(true);

        $this->validator->addValidator($customGridFieldValidator, 'stdClass');
        $this->validator->validate($entity, $content);
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException
     * @expectedExceptionMessage right message
     */
    public function testFailCustomValidate()
    {
        $classMetadata = $this->createMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $classMetadata
            ->expects(self::once())
            ->method('hasField')
            ->willReturn(true);
        $classMetadata
            ->expects(self::never())
            ->method('hasAssociation');

        $objectManager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $objectManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->registry
            ->expects(self::once())
            ->method('getManager')
            ->willReturn($objectManager);

        $entity  = new \StdClass();
        $content = [
            'field1' => 'val1'
        ];

        $this->translation
            ->expects(self::once())
            ->method('trans')
            ->willReturn('right message');

        $customGridFieldValidator = $this
            ->createMock('Oro\Bundle\EntityBundle\Entity\Manager\Field\CustomGridFieldValidatorInterface');
        $customGridFieldValidator
            ->expects(self::once())
            ->method('hasAccessEditField')
            ->willReturn(false);

        $this->validator->addValidator($customGridFieldValidator, 'stdClass');

        $this->validator->validate($entity, $content);
    }
}
