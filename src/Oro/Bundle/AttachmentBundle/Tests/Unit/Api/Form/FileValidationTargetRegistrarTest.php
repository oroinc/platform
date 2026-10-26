<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Form;

use Oro\Bundle\AttachmentBundle\Api\Form\FileValidationTargetRegistrar;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Model\FileValidationTarget;
use Oro\Bundle\AttachmentBundle\Provider\FileValidationTargetRegistry;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Stub\FileAwareEntityStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

final class FileValidationTargetRegistrarTest extends TestCase
{
    private FileValidationTargetRegistry $targetRegistry;
    private FileValidationTargetRegistrar $registrar;

    #[\Override]
    protected function setUp(): void
    {
        $this->targetRegistry = new FileValidationTargetRegistry();

        $this->registrar = new FileValidationTargetRegistrar($this->targetRegistry);
    }

    public function testRegisterForNewFile(): void
    {
        $file = new File();
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn(new \stdClass());
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));

        $this->registrar->register($file, $form);

        self::assertEquals(
            new FileValidationTarget(\stdClass::class, 'avatar'),
            $this->targetRegistry->get($file)
        );
        self::assertNull($file->getParentEntityClass());
        self::assertNull($file->getParentEntityId());
        self::assertNull($file->getParentEntityFieldName());
    }

    public function testRegisterWhenFileIsExisting(): void
    {
        $file = new File();
        ReflectionUtil::setId($file, 1);
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::never())
            ->method('getParent');

        $this->registrar->register($file, $form);

        self::assertNull($this->targetRegistry->get($file));
    }

    public function testRegisterTheSameTargetTwice(): void
    {
        $file = new File();
        $parent = new \stdClass();
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn($parent);
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));

        $this->registrar->register($file, $form);
        $this->registrar->register($file, $form);

        self::assertEquals(
            new FileValidationTarget(\stdClass::class, 'avatar'),
            $this->targetRegistry->get($file)
        );
    }

    public function testRegisterAnotherFieldOfTheSameParentForTheSameFile(): void
    {
        $file = new File();
        $parent = new \stdClass();
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn($parent);
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));
        $anotherForm = $this->createMock(FormInterface::class);
        $anotherForm->method('getParent')
            ->willReturn($parentForm);
        $anotherForm->method('getPropertyPath')
            ->willReturn(new PropertyPath('picture'));

        $this->registrar->register($file, $form);

        try {
            $this->registrar->register($file, $anotherForm);
            self::fail('The exception was expected.');
        } catch (TransformationFailedException $e) {
            self::assertSame('oro.attachment.parent.new_file_single_field', $e->getInvalidMessage());
        }

        self::assertEquals(
            new FileValidationTarget(\stdClass::class, 'avatar'),
            $this->targetRegistry->get($file)
        );
    }

    public function testRegisterAnotherParentOfTheSameClassForTheSameFile(): void
    {
        $file = new File();
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn(new \stdClass());
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));
        $anotherParentForm = $this->createMock(FormInterface::class);
        $anotherParentForm->method('getData')
            ->willReturn(new \stdClass());
        $anotherForm = $this->createMock(FormInterface::class);
        $anotherForm->method('getParent')
            ->willReturn($anotherParentForm);
        $anotherForm->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));

        $this->registrar->register($file, $form);

        try {
            $this->registrar->register($file, $anotherForm);
            self::fail('The exception was expected.');
        } catch (TransformationFailedException $e) {
            self::assertSame('oro.attachment.parent.new_file_single_field', $e->getInvalidMessage());
        }
    }

    public function testRegisterAnotherParentOfTheSameClassForTheSameFileAfterClear(): void
    {
        $file = new File();
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn(new \stdClass());
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));
        $anotherParentForm = $this->createMock(FormInterface::class);
        $anotherParentForm->method('getData')
            ->willReturn(new \stdClass());
        $anotherForm = $this->createMock(FormInterface::class);
        $anotherForm->method('getParent')
            ->willReturn($anotherParentForm);
        $anotherForm->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));

        $this->registrar->register($file, $form);
        $this->registrar->clear();
        $this->registrar->register($file, $anotherForm);

        self::assertEquals(
            new FileValidationTarget(\stdClass::class, 'avatar'),
            $this->targetRegistry->get($file)
        );
    }

    /**
     * @dataProvider anotherTargetDataProvider
     */
    public function testRegisterAnotherTargetWhenRegisteredParentIsNotKnown(
        object $anotherParent,
        string $anotherFieldName
    ): void {
        $file = new File();
        $this->targetRegistry->register($file, new FileValidationTarget(\stdClass::class, 'avatar'));
        $anotherParentForm = $this->createMock(FormInterface::class);
        $anotherParentForm->method('getData')
            ->willReturn($anotherParent);
        $anotherForm = $this->createMock(FormInterface::class);
        $anotherForm->method('getParent')
            ->willReturn($anotherParentForm);
        $anotherForm->method('getPropertyPath')
            ->willReturn(new PropertyPath($anotherFieldName));

        try {
            $this->registrar->register($file, $anotherForm);
            self::fail('The exception was expected.');
        } catch (TransformationFailedException $e) {
            self::assertSame('oro.attachment.parent.new_file_single_field', $e->getInvalidMessage());
        }

        self::assertEquals(
            new FileValidationTarget(\stdClass::class, 'avatar'),
            $this->targetRegistry->get($file)
        );
    }

    public function anotherTargetDataProvider(): array
    {
        return [
            'another field' => [
                'anotherParent' => new \stdClass(),
                'anotherFieldName' => 'picture',
            ],
            'another entity' => [
                'anotherParent' => new \ArrayObject(),
                'anotherFieldName' => 'avatar',
            ],
        ];
    }

    public function testRegisterTheSameTargetWhenRegisteredParentIsNotKnown(): void
    {
        $file = new File();
        $this->targetRegistry->register($file, new FileValidationTarget(\stdClass::class, 'avatar'));
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn(new \stdClass());
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));

        $this->registrar->register($file, $form);

        self::assertEquals(
            new FileValidationTarget(\stdClass::class, 'avatar'),
            $this->targetRegistry->get($file)
        );
    }

    public function testRegisterTheSameTargetWhenRegisteredParentWasReclaimed(): void
    {
        $file = new File();
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturnCallback(static fn () => new \stdClass());
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));
        $anotherParentForm = $this->createMock(FormInterface::class);
        $anotherParentForm->method('getData')
            ->willReturn(new \stdClass());
        $anotherForm = $this->createMock(FormInterface::class);
        $anotherForm->method('getParent')
            ->willReturn($anotherParentForm);
        $anotherForm->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));

        $this->registrar->register($file, $form);
        $this->registrar->register($file, $anotherForm);

        self::assertEquals(
            new FileValidationTarget(\stdClass::class, 'avatar'),
            $this->targetRegistry->get($file)
        );
    }

    public function testRegisterWhenSubmittedParentMatchesTheField(): void
    {
        $file = new File();
        $file->setParentEntityClass(\stdClass::class);
        $file->setParentEntityFieldName('avatar');
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn(new \stdClass());
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));

        $this->registrar->register($file, $form);

        self::assertEquals(
            new FileValidationTarget(\stdClass::class, 'avatar'),
            $this->targetRegistry->get($file)
        );
    }

    /**
     * @dataProvider submittedParentMismatchDataProvider
     */
    public function testRegisterWhenSubmittedParentDoesNotMatchTheField(
        ?string $submittedEntityClass,
        ?string $submittedFieldName
    ): void {
        $file = new File();
        $file->setParentEntityClass($submittedEntityClass);
        $file->setParentEntityFieldName($submittedFieldName);
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn(new \stdClass());
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));

        try {
            $this->registrar->register($file, $form);
            self::fail('The exception was expected.');
        } catch (TransformationFailedException $e) {
            self::assertSame('oro.attachment.parent.new_file_parent_mismatch', $e->getInvalidMessage());
        }

        self::assertNull($this->targetRegistry->get($file));
    }

    public function submittedParentMismatchDataProvider(): array
    {
        return [
            'another entity' => [
                'submittedEntityClass' => \ArrayObject::class,
                'submittedFieldName' => 'avatar',
            ],
            'another field' => [
                'submittedEntityClass' => \stdClass::class,
                'submittedFieldName' => 'picture',
            ],
            'no field name' => [
                'submittedEntityClass' => \stdClass::class,
                'submittedFieldName' => null,
            ],
        ];
    }

    public function testRegisterWhenSubmittedParentIdMatchesTheParentEntity(): void
    {
        $parent = new FileAwareEntityStub();
        ReflectionUtil::setId($parent, 1);
        $file = new File();
        $file->setParentEntityClass(FileAwareEntityStub::class);
        $file->setParentEntityFieldName('avatar');
        $file->setParentEntityId(1);
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn($parent);
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));

        $this->registrar->register($file, $form);

        self::assertEquals(
            new FileValidationTarget(FileAwareEntityStub::class, 'avatar'),
            $this->targetRegistry->get($file)
        );
    }

    public function testRegisterWhenSubmittedParentIdIsAnotherRecordOfTheSameClass(): void
    {
        $parent = new FileAwareEntityStub();
        ReflectionUtil::setId($parent, 1);
        $file = new File();
        $file->setParentEntityClass(FileAwareEntityStub::class);
        $file->setParentEntityFieldName('avatar');
        $file->setParentEntityId(2);
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn($parent);
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));

        try {
            $this->registrar->register($file, $form);
            self::fail('The exception was expected.');
        } catch (TransformationFailedException $e) {
            self::assertSame('oro.attachment.parent.new_file_parent_mismatch', $e->getInvalidMessage());
        }

        self::assertNull($this->targetRegistry->get($file));
    }

    public function testRegisterWhenSubmittedParentIdIsSpecifiedForNewParentEntity(): void
    {
        $parent = new FileAwareEntityStub();
        $file = new File();
        $file->setParentEntityClass(FileAwareEntityStub::class);
        $file->setParentEntityFieldName('avatar');
        $file->setParentEntityId(2);
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn($parent);
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));

        try {
            $this->registrar->register($file, $form);
            self::fail('The exception was expected.');
        } catch (TransformationFailedException $e) {
            self::assertSame('oro.attachment.parent.new_file_parent_mismatch', $e->getInvalidMessage());
        }

        self::assertNull($this->targetRegistry->get($file));
    }

    public function testRegisterWhenOnlySubmittedParentFieldNameIsSpecified(): void
    {
        $file = new File();
        $file->setParentEntityFieldName('picture');
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn(new \stdClass());
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($parentForm);
        $form->method('getPropertyPath')
            ->willReturn(new PropertyPath('avatar'));

        $this->registrar->register($file, $form);

        self::assertEquals(
            new FileValidationTarget(\stdClass::class, 'avatar'),
            $this->targetRegistry->get($file)
        );
    }

    /**
     * @dataProvider notRegisteredFormDataProvider
     */
    public function testRegisterWhenTargetCannotBeDetermined(
        bool $hasParentForm,
        mixed $parentData,
        ?PropertyPath $propertyPath
    ): void {
        $file = new File();
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->method('getData')
            ->willReturn($parentData);
        $form = $this->createMock(FormInterface::class);
        $form->method('getParent')
            ->willReturn($hasParentForm ? $parentForm : null);
        $form->method('getPropertyPath')
            ->willReturn($propertyPath);

        $this->registrar->register($file, $form);

        self::assertNull($this->targetRegistry->get($file));
    }

    public function notRegisteredFormDataProvider(): array
    {
        return [
            'no parent form' => [
                'hasParentForm' => false,
                'parentData' => new \stdClass(),
                'propertyPath' => new PropertyPath('avatar'),
            ],
            'parent data is not an object' => [
                'hasParentForm' => true,
                'parentData' => null,
                'propertyPath' => new PropertyPath('avatar'),
            ],
            'no property path' => [
                'hasParentForm' => true,
                'parentData' => new \stdClass(),
                'propertyPath' => null,
            ],
        ];
    }
}
