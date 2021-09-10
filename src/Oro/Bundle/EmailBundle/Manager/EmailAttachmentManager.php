<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Provides methods to manage email attachments.
 */
class EmailAttachmentManager
{
    /** @var FileManager */
    private $fileManager;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var RouterInterface */
    private $router;

    /** @var ConfigFileValidator */
    private $configFileValidator;

    /** @var AttachmentAssociationHelper */
    private $attachmentAssociationHelper;

    public function __construct(
        FileManager $fileManager,
        ManagerRegistry $doctrine,
        RouterInterface $router,
        ConfigFileValidator $configFileValidator,
        AttachmentAssociationHelper $attachmentAssociationHelper
    ) {
        $this->fileManager = $fileManager;
        $this->doctrine = $doctrine;
        $this->router = $router;
        $this->configFileValidator = $configFileValidator;
        $this->attachmentAssociationHelper = $attachmentAssociationHelper;
    }

    /**
     * Link attachment to scope
     *
     * @param EmailAttachment $emailAttachment
     * @param object          $entity
     */
    public function linkEmailAttachmentToTargetEntity(EmailAttachment $emailAttachment, $entity)
    {
        if (!$emailAttachment->getFile()) {
            $file = $this->saveEmailAttachmentToTemporaryFile($emailAttachment);
            $errors = $this->configFileValidator->validate($file, ClassUtils::getClass($entity));
            if ($errors->count() > 0) {
                @unlink($file->getPath());
            } else {
                $fileEntity = new File();
                $fileEntity->setFile($file);
                $emailAttachment->setFile($fileEntity);
                $this->linkAttachmentToEntity($emailAttachment, $entity);
            }
        }
    }

    /**
     * Validate file by target entity without saving.
     *
     * @param EmailAttachment $emailAttachment
     * @param string          $className
     *
     * @return ConstraintViolationListInterface
     */
    public function validateEmailAttachmentForTargetClass(EmailAttachment $emailAttachment, $className)
    {
        if (null === $emailAttachment->getFile()) {
            $file = $this->saveEmailAttachmentToTemporaryFile($emailAttachment);
            $fileViolations = $this->configFileValidator->validate($file, $className);
            @unlink($file->getPath());
        } else {
            $file = $emailAttachment->getFile()->getFile();
            $fileViolations = $this->configFileValidator->validate($file, $className);
        }

        return $fileViolations;
    }

    /**
     * Check is attached file to target entity
     *
     * @param EmailAttachment $attachment
     * @param object          $target
     *
     * @return bool
     */
    public function isAttached($attachment, $target)
    {
        $targetEntityClass = ClassUtils::getClass($target);
        if ($this->attachmentAssociationHelper->isAttachmentAssociationEnabled($targetEntityClass)) {
            $targetAssociationName = ExtendHelper::buildAssociationName($targetEntityClass);
            $attached = $this->doctrine->getRepository(Attachment::class)->findOneBy(
                [
                    $targetAssociationName => $target,
                    'file'                 => $attachment->getFile()
                ]
            );

            return null !== $attached;
        }

        return false;
    }

    /**
     * Get resized image url
     *
     * @param EmailAttachment $entity
     * @param int             $width
     * @param int             $height
     * @param int             $referenceType
     *
     * @return string
     */
    public function getResizedImageUrl(
        EmailAttachment $entity,
        $width = AttachmentManager::DEFAULT_IMAGE_WIDTH,
        $height = AttachmentManager::DEFAULT_IMAGE_HEIGHT,
        int $referenceType = RouterInterface::ABSOLUTE_PATH
    ) {
        return $this->router->generate(
            'oro_resize_email_attachment',
            [
                'id'     => $entity->getId(),
                'width'  => $width,
                'height' => $height,
            ],
            $referenceType
        );
    }

    /**
     * @param EmailAttachment $emailAttachment
     *
     * @return ComponentFile
     */
    private function saveEmailAttachmentToTemporaryFile(EmailAttachment $emailAttachment)
    {
        $content = ContentDecoder::decode(
            $emailAttachment->getContent()->getContent(),
            $emailAttachment->getContent()->getContentTransferEncoding()
        );

        $file = $this->fileManager->writeToTemporaryFile($content, $emailAttachment->getFileName());

        return new UploadedFile(
            $file->getPathname(),
            $emailAttachment->getFileName(),
            $emailAttachment->getContentType()
        );
    }

    /**
     * @param EmailAttachment $emailAttachment
     * @param object          $entity
     */
    private function linkAttachmentToEntity(EmailAttachment $emailAttachment, $entity)
    {
        $attachment = new Attachment();
        if (!$attachment->supportTarget(ClassUtils::getClass($entity))) {
            return;
        }

        $attachment->setFile($emailAttachment->getFile());
        $attachment->setTarget($entity);

        $em = $this->doctrine->getManagerForClass(Attachment::class);
        $em->persist($attachment);
        $em->flush();
    }
}
