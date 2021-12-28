<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
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

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AttachmentManagerTest extends \PHPUnit\Framework\TestCase
{
    private FileUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject $fileUrlProvider;

    private FileIconProvider|\PHPUnit\Framework\MockObject\MockObject $fileIconProvider;

    private MimeTypeChecker|\PHPUnit\Framework\MockObject\MockObject $mimeTypeChecker;

    private AssociationManager|\PHPUnit\Framework\MockObject\MockObject $associationManager;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $managerRegistry;

    private WebpConfiguration|\PHPUnit\Framework\MockObject\MockObject $webpConfiguration;

    private AttachmentManager $attachmentManager;

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
        $this->fileUrlProvider->expects(self::once())
            ->method('getFileUrl')
            ->with($file = new File(), $action = 'sample-action', $referenceType = 1)
            ->willReturn($url = '/sample-url');

        self::assertEquals($url, $this->attachmentManager->getFileUrl($file, $action, $referenceType));
    }

    public function testGetResizedImageUrl(): void
    {
        $this->fileUrlProvider->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($file = new File(), $width = 10, $height = 20, $format = 'sample-format', $referenceType = 1)
            ->willReturn($url = '/sample-url');

        self::assertEquals(
            $url,
            $this->attachmentManager->getResizedImageUrl($file, $width, $height, $format, $referenceType)
        );
    }

    public function testGetFilteredImageUrl(): void
    {
        $this->fileUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file = new File(), $filter = 'sample-filter', $format = 'sample-format', $referenceType = 1)
            ->willReturn($url = '/sample-url');

        self::assertEquals(
            $url,
            $this->attachmentManager->getFilteredImageUrl($file, $filter, $format, $referenceType)
        );
    }

    public function testGetFilteredImageUrlByIdAndFilename(): void
    {
        $this->managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(File::class)
            ->willReturn($entityManager = $this->createMock(EntityManager::class));

        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(File::class)
            ->willReturn($repo = $this->createMock(EntityRepository::class));

        $repo->expects(self::once())
            ->method('find')
            ->with($fileId = 1)
            ->willReturn($file = (new File())->setFilename($filename = 'sample-filename'));

        $this->fileUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, $filter = 'sample-filter', $format = 'sample-format', $referenceType = 1)
            ->willReturn($url = '/sample-url');

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
        $this->managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(File::class)
            ->willReturn($entityManager = $this->createMock(EntityManager::class));

        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(File::class)
            ->willReturn($repo = $this->createMock(EntityRepository::class));

        $repo->expects(self::once())
            ->method('find')
            ->with($fileId = 1)
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

        $this->fileIconProvider->expects(self::once())
            ->method('getExtensionIconClass')
            ->with($file)
            ->willReturn($icon = 'sample-icon');

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
        $this->fileIconProvider->expects(self::any())
            ->method('getFileIcons')
            ->willReturn($fileIcons = ['icon1', 'icon2']);

        self::assertEquals($fileIcons, $this->attachmentManager->getFileIcons());
    }

    public function testGetAttachmentTargets(): void
    {
        $this->associationManager->expects(self::once())
            ->method('getSingleOwnerFilter')
            ->with('attachment')
            ->willReturn(static fn () => null);

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with(
                AttachmentScope::ATTACHMENT,
                self::isType('callable'),
                RelationType::MANY_TO_ONE
            )
            ->willReturn($targets = ['sample_target_entity_class' => 'sample_field_name']);

        self::assertEquals($targets, $this->attachmentManager->getAttachmentTargets());
    }

    public function testIsWebpEnabledIfSupported(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn(true);

        self::assertTrue($this->attachmentManager->isWebpEnabledIfSupported());
    }

    public function testIsWebpEnabledForAll(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledForAll')
            ->willReturn(false);

        self::assertFalse($this->attachmentManager->isWebpEnabledForAll());
    }

    public function testIsWebpDisabled(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isDisabled')
            ->willReturn(false);

        self::assertFalse($this->attachmentManager->isWebpDisabled());
    }
}
