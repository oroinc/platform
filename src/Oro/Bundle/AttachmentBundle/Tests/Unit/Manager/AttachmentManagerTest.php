<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;
use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\FileIconProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AttachmentManagerTest extends TestCase
{
    private FileUrlProviderInterface&MockObject $fileUrlProvider;
    private FileIconProvider&MockObject $fileIconProvider;
    private MimeTypeChecker&MockObject $mimeTypeChecker;
    private AssociationManager&MockObject $associationManager;
    private ManagerRegistry&MockObject $managerRegistry;
    private WebpConfiguration&MockObject $webpConfiguration;
    private AttachmentManager $attachmentManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->fileUrlProvider = $this->createMock(FileUrlProviderInterface::class);
        $this->fileIconProvider = $this->createMock(FileIconProvider::class);
        $this->mimeTypeChecker = $this->createMock(MimeTypeChecker::class);
        $this->associationManager = $this->createMock(AssociationManager::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->webpConfiguration = $this->createMock(WebpConfiguration::class);

        $this->attachmentManager = new AttachmentManager(
            $this->fileUrlProvider,
            $this->fileIconProvider,
            $this->mimeTypeChecker,
            $this->associationManager,
            $this->managerRegistry,
            $this->webpConfiguration
        );
    }

    public function testGetFileUrl(): void
    {
        $file = new File();
        $action = 'sample-action';
        $referenceType = 1;
        $url = '/sample-url';
        $this->fileUrlProvider->expects(self::once())
            ->method('getFileUrl')
            ->with($file, $action, $referenceType)
            ->willReturn($url);

        self::assertEquals($url, $this->attachmentManager->getFileUrl($file, $action, $referenceType));
    }

    public function testGetResizedImageUrl(): void
    {
        $file = new File();
        $width = 10;
        $height = 20;
        $format = 'sample-format';
        $referenceType = 1;
        $url = '/sample-url';
        $this->fileUrlProvider->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($file, $width, $height, $format, $referenceType)
            ->willReturn($url);

        self::assertEquals(
            $url,
            $this->attachmentManager->getResizedImageUrl($file, $width, $height, $format, $referenceType)
        );
    }

    public function testGetFilteredImageUrl(): void
    {
        $file = new File();
        $filter = 'sample-filter';
        $format = 'sample-format';
        $referenceType = 1;
        $url = '/sample-url';
        $this->fileUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, $filter, $format, $referenceType)
            ->willReturn($url);

        self::assertEquals(
            $url,
            $this->attachmentManager->getFilteredImageUrl($file, $filter, $format, $referenceType)
        );
    }

    public function testGetFilteredImageUrlByIdAndFilename(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(File::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('getRepository')
            ->with(File::class)
            ->willReturn($repo);

        $filename = 'sample-filename';
        $file = (new File())->setFilename($filename);
        $fileId = 1;
        $repo->expects(self::once())
            ->method('find')
            ->with($fileId)
            ->willReturn($file);

        $url = '/sample-url';
        $this->fileUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, $filter = 'sample-filter', $format = 'sample-format', $referenceType = 1)
            ->willReturn($url);

        self::assertEquals(
            $url,
            $this->attachmentManager->getFilteredImageUrlByIdAndFilename(
                $fileId,
                $filename,
                $filter,
                $format,
                $referenceType
            )
        );
    }

    public function testGetFilteredImageUrlByIdAndFilenameWhenNoFile(): void
    {
        $fileId = 1;
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(File::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('getRepository')
            ->with(File::class)
            ->willReturn($repo);

        $repo->expects(self::once())
            ->method('find')
            ->with($fileId)
            ->willReturn(null);

        $this->fileUrlProvider->expects(self::never())
            ->method('getFilteredImageUrl');

        self::assertEmpty(
            $this->attachmentManager->getFilteredImageUrlByIdAndFilename(
                $fileId,
                'sample-filename',
                'sample-filter',
                1
            )
        );
    }

    public function testGetAttachmentIconClass(): void
    {
        $file = $this->createMock(FileExtensionInterface::class);
        $icon = 'sample-icon';

        $this->fileIconProvider->expects(self::once())
            ->method('getExtensionIconClass')
            ->with($file)
            ->willReturn($icon);

        self::assertEquals($icon, $this->attachmentManager->getAttachmentIconClass($file));
    }

    public function testIsImageType(): void
    {
        $this->mimeTypeChecker->expects(self::any())
            ->method('isImageMimeType')
            ->withConsecutive([$mimeType1 = 'sample/type'], [$mimeType2 = 'sample/not-image'])
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertTrue($this->attachmentManager->isImageType($mimeType1));
        self::assertFalse($this->attachmentManager->isImageType($mimeType2));
    }

    public function testGetFileIcons(): void
    {
        $fileIcons = ['icon1', 'icon2'];

        $this->fileIconProvider->expects(self::any())
            ->method('getFileIcons')
            ->willReturn($fileIcons);

        self::assertEquals($fileIcons, $this->attachmentManager->getFileIcons());
    }

    public function testGetAttachmentTargets(): void
    {
        $targets = ['sample_target_entity_class' => 'sample_field_name'];

        $this->associationManager->expects(self::once())
            ->method('getSingleOwnerFilter')
            ->with('attachment')
            ->willReturn(static fn () => null);

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with(AttachmentScope::ATTACHMENT, self::isType('callable'), RelationType::MANY_TO_ONE)
            ->willReturn($targets);

        self::assertEquals($targets, $this->attachmentManager->getAttachmentTargets());
    }

    public function testIsWebpEnabledIfSupported(): void
    {
        $this->webpConfiguration->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn(true);

        self::assertTrue($this->attachmentManager->isWebpEnabledIfSupported());
    }

    public function testIsWebpEnabledForAll(): void
    {
        $this->webpConfiguration->expects(self::once())
            ->method('isEnabledForAll')
            ->willReturn(false);

        self::assertFalse($this->attachmentManager->isWebpEnabledForAll());
    }

    public function testIsWebpDisabled(): void
    {
        $this->webpConfiguration->expects(self::once())
            ->method('isDisabled')
            ->willReturn(false);

        self::assertFalse($this->attachmentManager->isWebpDisabled());
    }
}
