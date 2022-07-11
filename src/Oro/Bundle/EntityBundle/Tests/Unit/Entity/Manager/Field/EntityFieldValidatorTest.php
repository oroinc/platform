<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Entity\Manager\Field;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\Entity\Manager\Field\CustomGridFieldValidatorInterface;
use Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldValidator;
use Oro\Bundle\EntityBundle\Exception\EntityHasFieldException;
use Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityFieldValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $classMetadata;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translation;

    /** @var EntityFieldValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->translation = $this->createMock(TranslatorInterface::class);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($this->classMetadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::once())
            ->method('getManager')
            ->willReturn($objectManager);

        $this->validator = new EntityFieldValidator($doctrine, $this->translation);
    }

    public function testPositiveValidate()
    {
        $this->classMetadata->expects(self::once())
            ->method('hasField')
            ->willReturn(true);
        $this->classMetadata->expects(self::never())
            ->method('hasAssociation');

        $entity = new \stdClass();
        $content = [
            'field1' => 'val1'
        ];

        $this->validator->validate($entity, $content);
    }

    public function testValidateWithFieldException()
    {
        $this->expectException(EntityHasFieldException::class);
        $this->expectExceptionMessage('oro.entity.controller.message.field_not_found');

        $this->classMetadata->expects(self::once())
            ->method('hasField')
            ->willReturn(false);
        $this->classMetadata->expects(self::once())
            ->method('hasAssociation')
            ->willReturn(false);

        $entity = new \stdClass();
        $content = [
            'field1' => 'val1'
        ];

        $this->validator->validate($entity, $content);
    }

    public function testValidateWithAccessException()
    {
        $this->expectException(FieldUpdateAccessException::class);
        $this->expectExceptionMessage('oro.entity.controller.message.access_denied');

        $this->classMetadata->expects(self::once())
            ->method('hasField')
            ->willReturn(true);
        $this->classMetadata->expects(self::never())
            ->method('hasAssociation');

        $entity = new \stdClass();
        $content = [
            'createdAt' => 'val1'
        ];

        $this->validator->validate($entity, $content);
    }

    public function testPositiveCustomValidate()
    {
        $this->classMetadata->expects(self::once())
            ->method('hasField')
            ->willReturn(true);
        $this->classMetadata->expects(self::never())
            ->method('hasAssociation');

        $entity = new \stdClass();
        $content = [
            'field1' => 'val1'
        ];

        $customGridFieldValidator = $this->createMock(CustomGridFieldValidatorInterface::class);
        $customGridFieldValidator->expects(self::once())
            ->method('hasAccessEditField')
            ->willReturn(true);

        $this->validator->addValidator($customGridFieldValidator, 'stdClass');
        $this->validator->validate($entity, $content);
    }

    public function testFailCustomValidate()
    {
        $this->expectException(FieldUpdateAccessException::class);
        $this->expectExceptionMessage('right message');

        $this->classMetadata->expects(self::once())
            ->method('hasField')
            ->willReturn(true);
        $this->classMetadata->expects(self::never())
            ->method('hasAssociation');

        $entity = new \stdClass();
        $content = [
            'field1' => 'val1'
        ];

        $this->translation->expects(self::once())
            ->method('trans')
            ->willReturn('right message');

        $customGridFieldValidator = $this->createMock(CustomGridFieldValidatorInterface::class);
        $customGridFieldValidator->expects(self::once())
            ->method('hasAccessEditField')
            ->willReturn(false);

        $this->validator->addValidator($customGridFieldValidator, 'stdClass');

        $this->validator->validate($entity, $content);
    }
}
