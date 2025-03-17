<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\PhpUtils\Formatter\BytesFormatter;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Provides attachments linked to an entity.
 */
class AttachmentProvider
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private AttachmentAssociationHelper $attachmentAssociationHelper,
        private AttachmentManager $attachmentManager,
        private PictureSourcesProviderInterface $pictureSourcesProvider,
        private PropertyAccessorInterface $propertyAccessor
    ) {
    }

    /**
     * @return Attachment[]
     */
    public function getEntityAttachments(object $entity): array
    {
        $entityClass = ClassUtils::getClass($entity);
        if (!$this->attachmentAssociationHelper->isAttachmentAssociationEnabled($entityClass)) {
            return [];
        }

        /** @var EntityRepository $repo */
        $repo = $this->doctrine->getRepository(Attachment::class);

        return $repo->createQueryBuilder('a')
            ->leftJoin('a.' . ExtendHelper::buildAssociationName($entityClass), 'entity')
            ->where('entity.id = :entityId')
            ->setParameter('entityId', $entity->getId())
            ->getQuery()
            ->getResult();
    }

    public function getAttachmentInfo(object $entity): array
    {
        $result = [];
        /** @var File|null $attachment */
        $attachment = $this->propertyAccessor->getValue($entity, 'attachment');
        if ($attachment && $attachment->getId()) {
            $thumbnail = '';
            $thumbnailSources = [];
            if ($this->attachmentManager->isImageType($attachment->getMimeType())) {
                $thumbnailPictureSources = $this->pictureSourcesProvider->getResizedPictureSources(
                    $attachment,
                    AttachmentManager::THUMBNAIL_WIDTH,
                    AttachmentManager::THUMBNAIL_HEIGHT
                );

                $thumbnail = $thumbnailPictureSources['src'];
                $thumbnailSources = $thumbnailPictureSources['sources'];
            }

            $attachmentPictureSources = $this->pictureSourcesProvider->getFilteredPictureSources($attachment);
            $result = [
                'attachmentURL' => [
                    'url' => $attachmentPictureSources['src'],
                    'sources' => $attachmentPictureSources['sources'],
                    'downloadUrl' => $this->attachmentManager->getFileUrl(
                        $attachment,
                        FileUrlProviderInterface::FILE_ACTION_DOWNLOAD
                    )
                ],
                'attachmentSize' => BytesFormatter::format($attachment->getFileSize()),
                'attachmentFileName' => $attachment->getOriginalFilename() ?: $attachment->getFilename(),
                'attachmentIcon' => $this->attachmentManager->getAttachmentIconClass($attachment),
                'attachmentThumbnailPicture' => ['src' => $thumbnail, 'sources' => $thumbnailSources]
            ];
        }

        return $result;
    }
}
