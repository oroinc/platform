<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Validator\Constraints\DigitalAssetSourceFileMimeType;
use Oro\Bundle\DigitalAssetBundle\Validator\Constraints\DigitalAssetSourceFileMimeTypeValidator;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DigitalAssetSourceFileMimeTypeValidatorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const SAMPLE_CLASS = 'SampleClass';
    private const SAMPLE_FIELD = 'sampleField';
    private const SAMPLE_ID = 1;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigManager;

    /** @var MimeTypeChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $mimeTypeChecker;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var DigitalAssetSourceFileMimeTypeValidator */
    private $validator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface */
    private $context;

    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(ConfigManager::class);
        $this->mimeTypeChecker = $this->createMock(MimeTypeChecker::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->validator = new DigitalAssetSourceFileMimeTypeValidator(
            $this->entityConfigManager,
            $this->mimeTypeChecker,
            $this->urlGenerator
        );

        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->setUpLoggerMock($this->validator);
    }

    public function testValidateWhenNotDigitalAsset(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instance of ' . DigitalAsset::class . ', got ' . \stdClass::class);

        $this->validator->validate(new \stdClass, $this->createMock(Constraint::class));
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected instance of ' . DigitalAssetSourceFileMimeType::class . ', got ' . get_class($constraint)
        );

        $this->validator->validate($this->createMock(DigitalAsset::class), $constraint);
    }

    public function testValidateWhenNoSourceFile(): void
    {
        $this->assertNoViolations();

        $this->validator->initialize($this->context);
        $this->validator->validate(
            $this->createMock(DigitalAsset::class),
            $this->createMock(DigitalAssetSourceFileMimeType::class)
        );
    }

    private function assertNoViolations(): void
    {
        $this->context
            ->expects($this->never())
            ->method('buildViolation');
    }

    public function testValidateWhenNoUploadedFile(): void
    {
        $digitalAsset = $this->createMock(DigitalAsset::class);
        $digitalAsset
            ->method('getSourceFile')
            ->willReturn($sourceFile = $this->createMock(File::class));

        $this->assertNoViolations();

        $this->validator->initialize($this->context);
        $this->validator->validate(
            $digitalAsset,
            $this->createMock(DigitalAssetSourceFileMimeType::class)
        );
    }

    public function testValidateWhenImage(): void
    {
        $digitalAsset = $this->createMock(DigitalAsset::class);
        $digitalAsset
            ->method('getSourceFile')
            ->willReturn($sourceFile = $this->createMock(File::class));

        $sourceFile
            ->method('getFile')
            ->willReturn($file = $this->createMock(ComponentFile::class));

        $file
            ->method('getMimeType')
            ->willReturn($imageMimeType = 'image/type');

        $this->mimeTypeChecker
            ->method('isImageMimeType')
            ->with($imageMimeType)
            ->willReturn(true);

        $this->assertNoViolations();

        $this->validator->initialize($this->context);
        $this->validator->validate(
            $digitalAsset,
            $this->createMock(DigitalAssetSourceFileMimeType::class)
        );
    }

    public function testValidateWhenPersistentCollection(): void
    {
        $childFiles = new PersistentCollection(
            $entityManager = $this->createMock(EntityManager::class),
            $this->createMock(ClassMetadata::class),
            new ArrayCollection()
        );
        $childFiles->setInitialized(false);
        $childFiles->setOwner(new \stdClass, ['inversedBy' => 'sample-data']);

        $entityManager
            ->method('getUnitOfWork')
            ->willReturn($this->createMock(UnitOfWork::class));

        $digitalAsset = $this->mockDigitalAsset($childFiles, $this->createMock(ComponentFile::class));

        $this->assertNoViolations();

        $this->validator->initialize($this->context);
        $this->validator->validate(
            $digitalAsset,
            $this->createMock(DigitalAssetSourceFileMimeType::class)
        );

        $reflection = new \ReflectionClass(PersistentCollection::class);
        $reflectionProperty = $reflection->getProperty('initialized');
        $reflectionProperty->setAccessible(true);
        $this->assertTrue($reflectionProperty->getValue($childFiles));
    }

    /**
     * @param Collection $childFiles
     * @param ComponentFile|\PHPUnit\Framework\MockObject\MockObject $uploadedFile
     *
     * @return DigitalAsset
     */
    private function mockDigitalAsset(Collection $childFiles, $uploadedFile): DigitalAsset
    {
        $digitalAsset = $this->getMockBuilder(DigitalAsset::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(['getSourceFile', 'getChildFiles'])
            ->getMock();

        $digitalAsset
            ->method('getSourceFile')
            ->willReturn($sourceFile = $this->createMock(File::class));

        $sourceFile
            ->method('getFile')
            ->willReturn($uploadedFile);

        $digitalAsset
            ->method('getChildFiles')
            ->willReturn($childFiles);

        $uploadedFile
            ->method('getMimeType')
            ->willReturn($fileMimeType = 'file/type');

        $this->mimeTypeChecker
            ->method('isImageMimeType')
            ->with($fileMimeType)
            ->willReturn(false);

        return $digitalAsset;
    }

    public function testValidateWhenNoParentEntityData(): void
    {
        $childFiles = new ArrayCollection([$this->createMock(File::class)]);

        $digitalAsset = $this->mockDigitalAsset($childFiles, $this->createMock(ComponentFile::class));

        $this->assertNoViolations();

        $this->assertLoggerWarningMethodCalled();

        $this->validator->initialize($this->context);
        $this->validator->validate(
            $digitalAsset,
            $this->createMock(DigitalAssetSourceFileMimeType::class)
        );
    }

    public function testValidateWhenNoConfigFieldModel(): void
    {
        $childFiles = $this->mockChildFiles();

        $digitalAsset = $this->mockDigitalAsset($childFiles, $this->createMock(ComponentFile::class));

        $this->entityConfigManager
            ->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(self::SAMPLE_CLASS, self::SAMPLE_FIELD)
            ->willReturn(null);

        $this->assertNoViolations();

        $this->validator->initialize($this->context);
        $this->validator->validate(
            $digitalAsset,
            $this->createMock(DigitalAssetSourceFileMimeType::class)
        );
    }

    private function mockChildFiles(): Collection
    {
        $childFiles = new ArrayCollection([$childFile = $this->createMock(File::class)]);

        $childFile
            ->method('getParentEntityClass')
            ->willReturn(self::SAMPLE_CLASS);

        $childFile
            ->method('getParentEntityFieldName')
            ->willReturn(self::SAMPLE_FIELD);

        $childFile
            ->method('getParentEntityId')
            ->willReturn(self::SAMPLE_ID);

        return $childFiles;
    }

    public function testValidateWhenFieldTypeNotImage(): void
    {
        $childFiles = $this->mockChildFiles();

        $digitalAsset = $this->mockDigitalAsset($childFiles, $this->createMock(ComponentFile::class));

        $this->mockFieldConfigModel('file');

        $this->assertNoViolations();

        $this->validator->initialize($this->context);
        $this->validator->validate(
            $digitalAsset,
            $this->createMock(DigitalAssetSourceFileMimeType::class)
        );
    }

    private function mockFieldConfigModel(string $type): void
    {
        $this->entityConfigManager
            ->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(self::SAMPLE_CLASS, self::SAMPLE_FIELD)
            ->willReturn($fieldConfigModel = $this->createMock(FieldConfigModel::class));

        $fieldConfigModel
            ->expects($this->once())
            ->method('getType')
            ->willReturn($type);
    }

    public function testValidateWhenNoEntityConfig(): void
    {
        $childFiles = $this->mockChildFiles();

        $digitalAsset = $this->mockDigitalAsset($childFiles, $uploadedFile = $this->createMock(ComponentFile::class));

        $this->mockFieldConfigModel('image');

        $uploadedFile
            ->expects($this->once())
            ->method('getFilename')
            ->willReturn($filename = 'sample-filename');

        $this->entityConfigManager
            ->method('getEntityMetadata')
            ->willReturn(null);

        $constraint = new DigitalAssetSourceFileMimeType();

        $this->assertViolation(
            $constraint->mimeTypeCannotBeNonImage,
            [
                '%file_name%' => $filename,
                '%field_name%' => self::SAMPLE_FIELD,
                '%entity_class%' => self::SAMPLE_CLASS,
                '%entity_id%' => self::SAMPLE_ID,
            ]
        );

        $this->validator->initialize($this->context);
        $this->validator->validate($digitalAsset, $constraint);
    }

    private function assertViolation(string $message, array $parameters): void
    {
        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($message, $parameters)
            ->willReturn($violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class));

        $violationBuilder
            ->expects($this->once())
            ->method('atPath')
            ->with('sourceFile.file')
            ->willReturnSelf();

        $violationBuilder
            ->expects($this->once())
            ->method('addViolation');
    }

    public function testValidateWhenEntityConfigHasNotRouteView(): void
    {
        $childFiles = $this->mockChildFiles();

        $digitalAsset = $this->mockDigitalAsset($childFiles, $uploadedFile = $this->createMock(UploadedFile::class));

        $this->mockFieldConfigModel('image');

        $uploadedFile
            ->expects($this->once())
            ->method('getClientOriginalName')
            ->willReturn($filename = 'sample-filename');

        $this->entityConfigManager
            ->method('getEntityMetadata')
            ->willReturn($entityMetadata = $this->createMock(EntityMetadata::class));

        $entityMetadata
            ->expects($this->once())
            ->method('hasRoute')
            ->with('view', true)
            ->willReturn(false);

        $constraint = new DigitalAssetSourceFileMimeType();

        $this->assertViolation(
            $constraint->mimeTypeCannotBeNonImage,
            [
                '%file_name%' => $filename,
                '%field_name%' => self::SAMPLE_FIELD,
                '%entity_class%' => self::SAMPLE_CLASS,
                '%entity_id%' => self::SAMPLE_ID,
            ]
        );

        $this->validator->initialize($this->context);
        $this->validator->validate($digitalAsset, $constraint);
    }

    public function testValidateWhenEntityConfigHasRouteView(): void
    {
        $childFiles = $this->mockChildFiles();

        $digitalAsset = $this->mockDigitalAsset($childFiles, $uploadedFile = $this->createMock(UploadedFile::class));

        $this->mockFieldConfigModel('image');

        $uploadedFile
            ->expects($this->once())
            ->method('getClientOriginalName')
            ->willReturn($filename = 'sample-filename');

        $this->entityConfigManager
            ->method('getEntityMetadata')
            ->willReturn($entityMetadata = $this->createMock(EntityMetadata::class));

        $entityMetadata
            ->expects($this->once())
            ->method('hasRoute')
            ->with('view', true)
            ->willReturn(true);

        $entityMetadata
            ->expects($this->once())
            ->method('getRoute')
            ->with('view')
            ->willReturn($viewRoute = 'sample-route');

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($viewRoute, ['id' => self::SAMPLE_ID])
            ->willReturn($entityUrl = '/sample/url');

        $constraint = new DigitalAssetSourceFileMimeType();

        $this->assertViolation(
            $constraint->mimeTypeCannotBeNonImageInEntity,
            [
                '%entity_url%' => $entityUrl,
                '%file_name%' => $filename,
                '%field_name%' => self::SAMPLE_FIELD,
                '%entity_class%' => self::SAMPLE_CLASS,
                '%entity_id%' => self::SAMPLE_ID,
            ]
        );

        $this->validator->initialize($this->context);
        $this->validator->validate($digitalAsset, $constraint);
    }
}
