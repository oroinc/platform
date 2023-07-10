<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\PhpUtils\Formatter\BytesFormatter;

/**
 * Provides attachments linked to an entity.
 */
class AttachmentProvider
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var AttachmentAssociationHelper */
    protected $attachmentAssociationHelper;

    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var PictureSourcesProviderInterface */
    private $pictureSourcesProvider;

    public function __construct(
        EntityManagerInterface $em,
        AttachmentAssociationHelper $attachmentAssociationHelper,
        AttachmentManager $attachmentManager,
        PictureSourcesProviderInterface $pictureSourcesProvider
    ) {
        $this->em = $em;
        $this->attachmentAssociationHelper = $attachmentAssociationHelper;
        $this->attachmentManager = $attachmentManager;
        $this->pictureSourcesProvider = $pictureSourcesProvider;
    }

    /**
     * @param object $entity
     *
     * @return Attachment[]
     */
    public function getEntityAttachments($entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        if ($this->attachmentAssociationHelper->isAttachmentAssociationEnabled($entityClass)) {
            $fieldName = ExtendHelper::buildAssociationName($entityClass);
            $repo = $this->em->getRepository(Attachment::class);

            $qb = $repo->createQueryBuilder('a');
            $qb->leftJoin('a.' . $fieldName, 'entity')
                ->where('entity.id = :entityId')
                ->setParameter('entityId', $entity->getId());

            return $qb->getQuery()->getResult();
        }

        return [];
    }

    /**
     * @param $entity
     *
     * @return File
     */
    private function getAttachmentByEntity($entity)
    {
        return (PropertyAccess::createPropertyAccessor())->getValue($entity, 'attachment');
    }

    /**
     * @param $entity
     *
     * @return array
     */
    public function getAttachmentInfo($entity)
    {
        $result     = [];
        $attachment = $this->getAttachmentByEntity($entity);
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
                    'downloadUrl' => $this->attachmentManager
                        ->getFileUrl($attachment, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD),
                ],
                'attachmentSize' => BytesFormatter::format($attachment->getFileSize()),
                'attachmentFileName' => $attachment->getOriginalFilename() ?: $attachment->getFilename(),
                'attachmentIcon' => $this->attachmentManager->getAttachmentIconClass($attachment),
                'attachmentThumbnailPicture' => [
                    'src' => $thumbnail,
                    'sources' => $thumbnailSources,
                ],
            ];
        }

        return $result;
    }
}
