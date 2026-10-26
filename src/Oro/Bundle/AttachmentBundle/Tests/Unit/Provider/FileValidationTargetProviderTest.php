<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Model\FileValidationTarget;
use Oro\Bundle\AttachmentBundle\Provider\FileValidationTargetProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileValidationTargetRegistry;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Test\ExtendedEntityTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FileValidationTargetProviderTest extends TestCase
{
    use ExtendedEntityTestTrait;

    private DoctrineHelper&MockObject $doctrineHelper;
    private FileValidationTargetRegistry $targetRegistry;
    private FileValidationTargetProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->targetRegistry = new FileValidationTargetRegistry();

        $this->provider = new FileValidationTargetProvider($this->doctrineHelper, $this->targetRegistry);
    }

    public function testGetTargetForFileWithoutParent(): void
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntity');

        self::assertEquals(FileValidationTarget::system(), $this->provider->getTargetForFile(new File()));
    }

    public function testGetTargetForFileRegisteredForSubmit(): void
    {
        $file = new File();
        $file->setParentEntityClass(TestClass::class);
        $file->setParentEntityId(1);
        $file->setParentEntityFieldName('avatar');
        $this->targetRegistry->register($file, new FileValidationTarget(\stdClass::class, 'picture'));

        $this->doctrineHelper->expects(self::never())
            ->method('getEntity');

        self::assertEquals(
            new FileValidationTarget(\stdClass::class, 'picture'),
            $this->provider->getTargetForFile($file)
        );
    }

    /**
     * @dataProvider parentEntityDataProvider
     */
    public function testGetTargetForFileWithParentEntity(?string $fieldName, string $expectedFieldName): void
    {
        $file = new File();
        $file->setParentEntityClass(TestClass::class);
        $file->setParentEntityId(1);
        $file->setParentEntityFieldName($fieldName);

        $this->doctrineHelper->expects(self::never())
            ->method('getEntity');

        self::assertEquals(
            new FileValidationTarget(TestClass::class, $expectedFieldName),
            $this->provider->getTargetForFile($file)
        );
    }

    public function parentEntityDataProvider(): array
    {
        return [
            'with field name' => [
                'fieldName' => 'avatar',
                'expectedFieldName' => 'avatar',
            ],
            'without field name' => [
                'fieldName' => null,
                'expectedFieldName' => '',
            ],
        ];
    }

    public function testGetTargetForFileOfAttachment(): void
    {
        $file = new File();
        $file->setParentEntityClass(Attachment::class);
        $file->setParentEntityId(10);
        $file->setParentEntityFieldName('file');
        $target = new TestClass();
        $attachment = new Attachment();
        $this->entityFieldTestExtension->addExpectation(
            Attachment::class,
            'getTarget',
            function (array $arguments, object $object, mixed &$result) use ($attachment, $target) {
                self::assertSame($attachment, $object);
                $result = $target;

                return true;
            }
        );

        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(Attachment::class, 10)
            ->willReturn($attachment);
        $this->doctrineHelper->expects(self::once())
            ->method('getClass')
            ->with(self::identicalTo($target))
            ->willReturn(TestClass::class);

        self::assertEquals(
            new FileValidationTarget(TestClass::class, ''),
            $this->provider->getTargetForFile($file)
        );
    }

    public function testGetTargetForFileOfNotExistingAttachment(): void
    {
        $file = new File();
        $file->setParentEntityClass(Attachment::class);
        $file->setParentEntityId(10);
        $file->setParentEntityFieldName('file');

        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(Attachment::class, 10)
            ->willReturn(null);

        self::assertEquals(
            new FileValidationTarget(Attachment::class, 'file'),
            $this->provider->getTargetForFile($file)
        );
    }

    public function testGetTargetForFileOfAttachmentWithoutId(): void
    {
        $file = new File();
        $file->setParentEntityClass(Attachment::class);
        $file->setParentEntityFieldName('file');

        $this->doctrineHelper->expects(self::never())
            ->method('getEntity');

        self::assertEquals(
            new FileValidationTarget(Attachment::class, 'file'),
            $this->provider->getTargetForFile($file)
        );
    }

    public function testGetTargetForFileOfAttachmentWithoutTarget(): void
    {
        $file = new File();
        $file->setParentEntityClass(Attachment::class);
        $file->setParentEntityId(10);
        $file->setParentEntityFieldName('file');
        $this->entityFieldTestExtension->addExpectation(
            Attachment::class,
            'getTarget',
            function (array $arguments, object $object, mixed &$result) {
                $result = null;

                return true;
            }
        );

        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(Attachment::class, 10)
            ->willReturn(new Attachment());

        self::assertEquals(
            new FileValidationTarget(Attachment::class, 'file'),
            $this->provider->getTargetForFile($file)
        );
    }

    public function testGetTargetForFileOfAttachmentCustomField(): void
    {
        $file = new File();
        $file->setParentEntityClass(Attachment::class);
        $file->setParentEntityId(10);
        $file->setParentEntityFieldName('customImage');

        $this->doctrineHelper->expects(self::never())
            ->method('getEntity');

        self::assertEquals(
            new FileValidationTarget(Attachment::class, 'customImage'),
            $this->provider->getTargetForFile($file)
        );
    }

    public function testGetTargetForAttachment(): void
    {
        $target = new TestClass();
        $attachment = new Attachment();
        $this->entityFieldTestExtension->addExpectation(
            Attachment::class,
            'getTarget',
            function (array $arguments, object $object, mixed &$result) use ($target) {
                $result = $target;

                return true;
            }
        );

        $this->doctrineHelper->expects(self::once())
            ->method('getClass')
            ->with(self::identicalTo($target))
            ->willReturn(TestClass::class);

        self::assertEquals(
            new FileValidationTarget(TestClass::class),
            $this->provider->getTargetForAttachment($attachment)
        );
    }

    public function testGetTargetForAttachmentWithoutTarget(): void
    {
        $this->entityFieldTestExtension->addExpectation(
            Attachment::class,
            'getTarget',
            function (array $arguments, object $object, mixed &$result) {
                $result = null;

                return true;
            }
        );

        $this->doctrineHelper->expects(self::never())
            ->method('getClass');

        self::assertNull($this->provider->getTargetForAttachment(new Attachment()));
    }

    /**
     * @dataProvider isSubmittedWithAttachmentDataProvider
     */
    public function testIsSubmittedWithAttachment(
        File $file,
        ?FileValidationTarget $registeredTarget,
        bool $expected
    ): void {
        if (null !== $registeredTarget) {
            $this->targetRegistry->register($file, $registeredTarget);
        }

        self::assertSame($expected, $this->provider->isSubmittedWithAttachment($file));
    }

    public function isSubmittedWithAttachmentDataProvider(): array
    {
        return [
            'new file submitted to the file field of an attachment' => [
                'file' => new File(),
                'registeredTarget' => new FileValidationTarget(Attachment::class, 'file'),
                'expected' => true
            ],
            'new file submitted to a custom field of an attachment' => [
                'file' => new File(),
                'registeredTarget' => new FileValidationTarget(Attachment::class, 'customImage'),
                'expected' => false
            ],
            'new file submitted to the file field of another entity' => [
                'file' => new File(),
                'registeredTarget' => new FileValidationTarget(TestClass::class, 'file'),
                'expected' => false
            ],
            'file that is not submitted' => [
                'file' => new File(),
                'registeredTarget' => null,
                'expected' => false
            ],
        ];
    }
}
