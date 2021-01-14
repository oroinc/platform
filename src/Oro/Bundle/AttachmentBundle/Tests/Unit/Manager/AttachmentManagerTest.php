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
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestFile;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AttachmentManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileUrlProvider;

    /** @var FileIconProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fileIconProvider;

    /** @var MimeTypeChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $mimeTypeChecker;

    /** @var AssociationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $associationManager;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var TestFile */
    private $file;

    protected function setUp(): void
    {
        $this->fileUrlProvider = $this->createMock(FileUrlProviderInterface::class);
        $this->fileIconProvider = $this->createMock(FileIconProvider::class);
        $this->mimeTypeChecker = $this->createMock(MimeTypeChecker::class);
        $this->associationManager = $this->createMock(AssociationManager::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->file = new TestFile();
        $this->file->setFilename('testFile.txt');
        $this->file->setOriginalFilename('testFile.txt');

        $this->attachmentManager = new AttachmentManager(
            $this->fileUrlProvider,
            $this->fileIconProvider,
            $this->mimeTypeChecker,
            $this->associationManager,
            $this->urlGenerator,
            $this->registry
        );
    }

    public function testGetFileRestApiUrl(): void
    {
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with('oro_api_get_file', ['id' => $fileId = 1, '_format' => 'binary'])
            ->willReturn($url = '/sample-url');

        self::assertEquals($url, $this->attachmentManager->getFileRestApiUrl($fileId));
    }

    public function testGetFileUrl(): void
    {
        $this->fileUrlProvider
            ->expects(self::once())
            ->method('getFileUrl')
            ->with($file = new File(), $action = 'sample-action', $referenceType = 1)
            ->willReturn($url = '/sample-url');

        self::assertEquals($url, $this->attachmentManager->getFileUrl($file, $action, $referenceType));
    }

    public function testGetResizedImageUrl(): void
    {
        $this->fileUrlProvider
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($file = new File(), $width = 10, $height = 20, $referenceType = 1)
            ->willReturn($url = '/sample-url');

        self::assertEquals($url, $this->attachmentManager->getResizedImageUrl($file, $width, $height, $referenceType));
    }

    public function testGetFilteredImageUrl(): void
    {
        $this->fileUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file = new File(), $filter = 'sample-filter', $referenceType = 1)
            ->willReturn($url = '/sample-url');

        self::assertEquals($url, $this->attachmentManager->getFilteredImageUrl($file, $filter, $referenceType));
    }

    public function testGetFilteredImageUrlByIdAndFilename(): void
    {
        $this->registry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(File::class)
            ->willReturn($entityManager = $this->createMock(EntityManager::class));

        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(File::class)
            ->willReturn($repo = $this->createMock(EntityRepository::class));

        $repo
            ->expects(self::once())
            ->method('find')
            ->with($fileId = 1)
            ->willReturn($file = (new File())->setFilename($filename = 'sample-filename'));

        $this->fileUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, $filter = 'sample-filter', $referenceType = 1)
            ->willReturn($url = '/sample-url');

        self::assertEquals(
            $url,
            $this->attachmentManager->getFilteredImageUrlByIdAndFilename($fileId, $filename, $filter, $referenceType)
        );
    }

    public function testGetFilteredImageUrlByIdAndFilenameWhenNoFile(): void
    {
        $this->registry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(File::class)
            ->willReturn($entityManager = $this->createMock(EntityManager::class));

        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(File::class)
            ->willReturn($repo = $this->createMock(EntityRepository::class));

        $repo
            ->expects(self::once())
            ->method('find')
            ->with($fileId = 1)
            ->willReturn(null);

        $this->fileUrlProvider
            ->expects(self::never())
            ->method('getFilteredImageUrl');

        self::assertEmpty($this->attachmentManager->getFilteredImageUrlByIdAndFilename(
            $fileId,
            'sample-filename',
            'sample-filter',
            1
        ));
    }

    public function testGetAttachmentIconClass(): void
    {
        $file = $this->createMock(FileExtensionInterface::class);

        $this->fileIconProvider
            ->expects(self::once())
            ->method('getExtensionIconClass')
            ->with($file)
            ->willReturn($icon = 'sample-icon');

        self::assertEquals($icon, $this->attachmentManager->getAttachmentIconClass($file));
    }

    public function testIsImageType(): void
    {
        $this->mimeTypeChecker
            ->method('isImageMimeType')
            ->withConsecutive([$mimeType1 = 'sample/type'], [$mimeType2 = 'sample/not-image'])
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertTrue($this->attachmentManager->isImageType($mimeType1));
        self::assertFalse($this->attachmentManager->isImageType($mimeType2));
    }

    public function testGetFileIcons(): void
    {
        $this->fileIconProvider
            ->method('getFileIcons')
            ->willReturn($fileIcons = ['icon1', 'icon2']);

        self::assertEquals($fileIcons, $this->attachmentManager->getFileIcons());
    }

    public function testGetAttachmentTargets(): void
    {
        $this->associationManager
            ->expects(self::once())
            ->method('getSingleOwnerFilter')
            ->with('attachment')
            ->willReturn(function () {
            });

        $this->associationManager
            ->expects(self::once())
            ->method('getAssociationTargets')
            ->with(
                AttachmentScope::ATTACHMENT,
                $this->isType('callable'),
                RelationType::MANY_TO_ONE
            )
            ->willReturn($targets = ['sample_target_cntity_class' => 'sample_field_name']);

        self::assertEquals($targets, $this->attachmentManager->getAttachmentTargets());
    }
}
