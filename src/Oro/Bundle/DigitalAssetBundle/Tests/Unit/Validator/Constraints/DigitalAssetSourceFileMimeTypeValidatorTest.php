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
use Oro\Bundle\DigitalAssetBundle\Tests\Unit\Stub\DigitalAssetStub;
use Oro\Bundle\DigitalAssetBundle\Validator\Constraints\DigitalAssetSourceFileMimeType;
use Oro\Bundle\DigitalAssetBundle\Validator\Constraints\DigitalAssetSourceFileMimeTypeValidator;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DigitalAssetSourceFileMimeTypeValidatorTest extends ConstraintValidatorTestCase
{
    private const SAMPLE_CLASS = 'SampleClass';
    private const SAMPLE_FIELD = 'sampleField';
    private const SAMPLE_ID = 1;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigManager;

    /** @var MimeTypeChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $mimeTypeChecker;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(ConfigManager::class);
        $this->mimeTypeChecker = $this->createMock(MimeTypeChecker::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        parent::setUp();
    }

    protected function createValidator(): DigitalAssetSourceFileMimeTypeValidator
    {
        $validator = new DigitalAssetSourceFileMimeTypeValidator(
            $this->entityConfigManager,
            $this->mimeTypeChecker,
            $this->urlGenerator
        );
        $validator->setLogger($this->logger);

        return $validator;
    }

    public function testGetTargets(): void
    {
        $constraint = new DigitalAssetSourceFileMimeType();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateWhenNotDigitalAsset(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instance of ' . DigitalAsset::class . ', got ' . \stdClass::class);

        $this->validator->validate(new \stdClass(), new DigitalAssetSourceFileMimeType());
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
        $constraint = new DigitalAssetSourceFileMimeType();
        $this->validator->validate($this->createMock(DigitalAsset::class), $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWhenNoUploadedFile(): void
    {
        $digitalAsset = $this->createMock(DigitalAsset::class);
        $digitalAsset->expects($this->any())
            ->method('getSourceFile')
            ->willReturn($this->createMock(File::class));

        $constraint = new DigitalAssetSourceFileMimeType();
        $this->validator->validate($digitalAsset, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWhenImage(): void
    {
        $imageMimeType = 'image/type';
        $file = $this->createMock(ComponentFile::class);
        $file->expects($this->any())
            ->method('getMimeType')
            ->willReturn($imageMimeType);

        $sourceFile = $this->createMock(File::class);
        $sourceFile->expects($this->any())
            ->method('getFile')
            ->willReturn($file);

        $this->mimeTypeChecker->expects($this->any())
            ->method('isImageMimeType')
            ->with($imageMimeType)
            ->willReturn(true);

        $digitalAsset = $this->createMock(DigitalAsset::class);
        $digitalAsset->expects($this->any())
            ->method('getSourceFile')
            ->willReturn($sourceFile);

        $constraint = new DigitalAssetSourceFileMimeType();
        $this->validator->validate($digitalAsset, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWhenPersistentCollection(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $childFiles = new PersistentCollection(
            $entityManager,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection()
        );
        $childFiles->setInitialized(false);
        $childFiles->setOwner(new \stdClass, ['inversedBy' => 'sample-data']);

        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->createMock(UnitOfWork::class));

        $digitalAsset = $this->getDigitalAsset($childFiles, $this->createMock(ComponentFile::class));

        $constraint = new DigitalAssetSourceFileMimeType();
        $this->validator->validate($digitalAsset, $constraint);
        $this->assertNoViolation();

        $this->assertTrue(ReflectionUtil::getPropertyValue($childFiles, 'initialized'));
    }

    private function getDigitalAsset(
        Collection $childFiles,
        ComponentFile|\PHPUnit\Framework\MockObject\MockObject $uploadedFile
    ): DigitalAsset {
        $fileMimeType = 'file/type';

        $sourceFile = $this->createMock(File::class);
        $sourceFile->expects($this->any())
            ->method('getFile')
            ->willReturn($uploadedFile);

        $uploadedFile->expects($this->any())
            ->method('getMimeType')
            ->willReturn($fileMimeType);

        $this->mimeTypeChecker->expects($this->any())
            ->method('isImageMimeType')
            ->with($fileMimeType)
            ->willReturn(false);

        $digitalAsset = $this->createMock(DigitalAssetStub::class);
        $digitalAsset->expects($this->any())
            ->method('getSourceFile')
            ->willReturn($sourceFile);
        $digitalAsset->expects($this->any())
            ->method('getChildFiles')
            ->willReturn($childFiles);

        return $digitalAsset;
    }

    public function testValidateWhenNoParentEntityData(): void
    {
        $childFiles = new ArrayCollection([$this->createMock(File::class)]);

        $digitalAsset = $this->getDigitalAsset($childFiles, $this->createMock(ComponentFile::class));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('File # does not have parent entity data');

        $constraint = new DigitalAssetSourceFileMimeType();
        $this->validator->validate($digitalAsset, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWhenNoConfigFieldModel(): void
    {
        $childFiles = $this->getChildFiles();

        $digitalAsset = $this->getDigitalAsset($childFiles, $this->createMock(ComponentFile::class));

        $this->entityConfigManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(self::SAMPLE_CLASS, self::SAMPLE_FIELD)
            ->willReturn(null);

        $constraint = new DigitalAssetSourceFileMimeType();
        $this->validator->validate($digitalAsset, $constraint);
        $this->assertNoViolation();
    }

    private function getChildFiles(): Collection
    {
        $childFile = $this->createMock(File::class);
        $childFiles = new ArrayCollection([$childFile]);
        $childFile->expects($this->any())
            ->method('getParentEntityClass')
            ->willReturn(self::SAMPLE_CLASS);
        $childFile->expects($this->any())
            ->method('getParentEntityFieldName')
            ->willReturn(self::SAMPLE_FIELD);
        $childFile->expects($this->any())
            ->method('getParentEntityId')
            ->willReturn(self::SAMPLE_ID);

        return $childFiles;
    }

    public function testValidateWhenFieldTypeNotImage(): void
    {
        $childFiles = $this->getChildFiles();

        $digitalAsset = $this->getDigitalAsset($childFiles, $this->createMock(ComponentFile::class));

        $this->mockFieldConfigModel('file');

        $constraint = new DigitalAssetSourceFileMimeType();
        $this->validator->validate($digitalAsset, $constraint);
        $this->assertNoViolation();
    }

    private function mockFieldConfigModel(string $type): void
    {
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);

        $this->entityConfigManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(self::SAMPLE_CLASS, self::SAMPLE_FIELD)
            ->willReturn($fieldConfigModel);

        $fieldConfigModel->expects($this->once())
            ->method('getType')
            ->willReturn($type);
    }

    public function testValidateWhenNoEntityConfig(): void
    {
        $childFiles = $this->getChildFiles();

        $uploadedFile = $this->createMock(ComponentFile::class);
        $digitalAsset = $this->getDigitalAsset($childFiles, $uploadedFile);

        $this->mockFieldConfigModel('image');

        $uploadedFile->expects($this->once())
            ->method('getFilename')
            ->willReturn($filename = 'sample-filename');

        $this->entityConfigManager->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturn(null);

        $constraint = new DigitalAssetSourceFileMimeType();
        $this->validator->validate($digitalAsset, $constraint);

        $this->buildViolation($constraint->mimeTypeCannotBeNonImage)
            ->setParameters([
                '%file_name%' => $filename,
                '%field_name%' => self::SAMPLE_FIELD,
                '%entity_class%' => self::SAMPLE_CLASS,
                '%entity_id%' => self::SAMPLE_ID,
            ])
            ->atPath('property.path.sourceFile.file')
            ->assertRaised();
    }

    public function testValidateWhenEntityConfigHasNotRouteView(): void
    {
        $childFiles = $this->getChildFiles();

        $uploadedFile = $this->createMock(UploadedFile::class);
        $digitalAsset = $this->getDigitalAsset($childFiles, $uploadedFile);

        $this->mockFieldConfigModel('image');

        $uploadedFile->expects($this->once())
            ->method('getClientOriginalName')
            ->willReturn($filename = 'sample-filename');

        $this->entityConfigManager->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturn(new EntityMetadata(\stdClass::class));

        $constraint = new DigitalAssetSourceFileMimeType();
        $this->validator->validate($digitalAsset, $constraint);

        $this->buildViolation($constraint->mimeTypeCannotBeNonImage)
            ->setParameters([
                '%file_name%' => $filename,
                '%field_name%' => self::SAMPLE_FIELD,
                '%entity_class%' => self::SAMPLE_CLASS,
                '%entity_id%' => self::SAMPLE_ID,
            ])
            ->atPath('property.path.sourceFile.file')
            ->assertRaised();
    }

    public function testValidateWhenEntityConfigHasRouteView(): void
    {
        $childFiles = $this->getChildFiles();

        $uploadedFile = $this->createMock(UploadedFile::class);
        $digitalAsset = $this->getDigitalAsset($childFiles, $uploadedFile);

        $viewRoute = 'sample-route';
        $entityMetadata = new EntityMetadata(\stdClass::class);
        $entityMetadata->routeView = $viewRoute;

        $entityUrl = '/sample/url';

        $this->mockFieldConfigModel('image');

        $uploadedFile->expects($this->once())
            ->method('getClientOriginalName')
            ->willReturn($filename = 'sample-filename');

        $this->entityConfigManager->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturn($entityMetadata);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($viewRoute, ['id' => self::SAMPLE_ID])
            ->willReturn($entityUrl);

        $constraint = new DigitalAssetSourceFileMimeType();
        $this->validator->validate($digitalAsset, $constraint);

        $this->buildViolation($constraint->mimeTypeCannotBeNonImageInEntity)
            ->setParameters([
                '%entity_url%' => $entityUrl,
                '%file_name%' => $filename,
                '%field_name%' => self::SAMPLE_FIELD,
                '%entity_class%' => self::SAMPLE_CLASS,
                '%entity_id%' => self::SAMPLE_ID,
            ])
            ->atPath('property.path.sourceFile.file')
            ->assertRaised();
    }
}
