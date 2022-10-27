<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\AttachmentAwareTestClass;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestFile;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;

class AttachmentProviderTest extends \PHPUnit\Framework\TestCase
{
    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $em;

    private AttachmentAssociationHelper|\PHPUnit\Framework\MockObject\MockObject $attachmentAssociationHelper;

    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private PictureSourcesProviderInterface|\PHPUnit\Framework\MockObject\MockObject $pictureSourcesProvider;

    private AttachmentProvider $attachmentProvider;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->attachmentAssociationHelper = $this->createMock(AttachmentAssociationHelper::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->pictureSourcesProvider = $this->createMock(PictureSourcesProviderInterface::class);

        $this->attachmentProvider = new AttachmentProvider(
            $this->em,
            $this->attachmentAssociationHelper,
            $this->attachmentManager,
            $this->pictureSourcesProvider
        );
    }

    public function testGetEntityAttachments(): void
    {
        $entity = new TestClass();

        $this->attachmentAssociationHelper->expects($this->once())
            ->method('isAttachmentAssociationEnabled')
            ->with(get_class($entity))
            ->willReturn(true);

        $repo = $this->createMock(EntityRepository::class);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroAttachmentBundle:Attachment')
            ->willReturn($repo);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('leftJoin')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('where')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('setParameter')
            ->willReturn($qb);

        $query = $this->createMock(AbstractQuery::class);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn('result');

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($qb);

        $result = $this->attachmentProvider->getEntityAttachments($entity);

        self::assertEquals('result', $result);
    }

    public function testUnsupportedAttachments(): void
    {
        $entity = new TestClass();

        $this->attachmentAssociationHelper->expects($this->once())
            ->method('isAttachmentAssociationEnabled')
            ->with(get_class($entity))
            ->willReturn(false);

        $result = $this->attachmentProvider->getEntityAttachments($entity);

        self::assertEquals([], $result);
    }

    public function testGetAttachmentInfoEmptyAttachment(): void
    {
        $entity = new AttachmentAwareTestClass();

        $result = $this->attachmentProvider->getAttachmentInfo($entity);

        self::assertEquals([], $result);
    }

    public function testGetAttachmentInfoNotIsWebpEnabledIfSupported(): void
    {
        $attachment = (new TestFile())
            ->setId(1)
            ->setMimeType('image/jpeg')
            ->setFileSize(500)
            ->setOriginalFilename('original_file_name');
        $entity = new AttachmentAwareTestClass();
        $entity->setAttachment($attachment);

        $this->attachmentManager
            ->expects(self::once())
            ->method('isImageType')
            ->with('image/jpeg')
            ->willReturn(true);

        $this->pictureSourcesProvider
            ->expects(self::once())
            ->method('getResizedPictureSources')
            ->with($attachment, AttachmentManager::THUMBNAIL_WIDTH, AttachmentManager::THUMBNAIL_HEIGHT)
            ->willReturn([
                'src' => '/url/thumbnail.jpg',
                'sources' => [],
            ]);
        $this->pictureSourcesProvider
            ->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($attachment)
            ->willReturn([
                'src' => '/url/file.jpg',
                'sources' => [],
            ]);

        $this->attachmentManager
            ->expects(self::once())
            ->method('getFileUrl')
            ->with($attachment, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD)
            ->willReturn('/attachment/download/file.jpg');
        $this->attachmentManager
            ->expects(self::never())
            ->method('getFilteredImageUrl');
        $this->attachmentManager
            ->expects(self::once())
            ->method('getAttachmentIconClass')
            ->with($attachment)
            ->willReturn('fa-file-o');

        $result = $this->attachmentProvider->getAttachmentInfo($entity);

        self::assertEquals(
            [
                'attachmentURL' => [
                    'url' => '/url/file.jpg',
                    'sources' => [],
                    'downloadUrl' => '/attachment/download/file.jpg',
                ],
                'attachmentSize' => '500.00 B',
                'attachmentFileName' => 'original_file_name',
                'attachmentIcon' => 'fa-file-o',
                'attachmentThumbnailPicture' => [
                    'src' => '/url/thumbnail.jpg',
                    'sources' => [],
                ],
            ],
            $result
        );
    }

    public function testGetAttachmentInfoIsWebpEnabledIfSupported(): void
    {
        $attachment = (new TestFile())
            ->setId(1)
            ->setMimeType('image/jpeg')
            ->setFileSize(500)
            ->setOriginalFilename('original_file_name');
        $entity = new AttachmentAwareTestClass();
        $entity->setAttachment($attachment);

        $this->attachmentManager
            ->expects(self::once())
            ->method('isImageType')
            ->with('image/jpeg')
            ->willReturn(true);

        $this->pictureSourcesProvider
            ->expects(self::once())
            ->method('getResizedPictureSources')
            ->with($attachment, AttachmentManager::THUMBNAIL_WIDTH, AttachmentManager::THUMBNAIL_HEIGHT)
            ->willReturn([
                'src' => '/url/thumbnail.jpg',
                'sources' => [
                    [
                        'srcset' => '/url/thumbnail.jpg.webp',
                        'type' => 'image/webp',
                    ],
                ],
            ]);
        $this->pictureSourcesProvider
            ->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($attachment)
            ->willReturn([
                'src' => '/url/file.jpg',
                'sources' => [
                    [
                        'srcset' => '/url/to/original/filtered/file.jpg.webp',
                        'type' => 'image/webp',
                    ],
                ],
            ]);

        $this->attachmentManager
            ->expects(self::once())
            ->method('getFileUrl')
            ->with($attachment, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD)
            ->willReturn('/attachment/download/file.jpg');
        $this->attachmentManager
            ->expects(self::once())
            ->method('getAttachmentIconClass')
            ->with($attachment)
            ->willReturn('fa-file-o');

        $result = $this->attachmentProvider->getAttachmentInfo($entity);

        self::assertEquals(
            [
                'attachmentURL' => [
                    'url' => '/url/file.jpg',
                    'sources' => [
                        [
                            'srcset' => '/url/to/original/filtered/file.jpg.webp',
                            'type' => 'image/webp',
                        ],
                    ],
                    'downloadUrl' => '/attachment/download/file.jpg',
                ],
                'attachmentSize' => '500.00 B',
                'attachmentFileName' => 'original_file_name',
                'attachmentIcon' => 'fa-file-o',
                'attachmentThumbnailPicture' => [
                    'src' => '/url/thumbnail.jpg',
                    'sources' => [
                        [
                            'srcset' => '/url/thumbnail.jpg.webp',
                            'type' => 'image/webp',
                        ],
                    ],
                ],
            ],
            $result
        );
    }
}
