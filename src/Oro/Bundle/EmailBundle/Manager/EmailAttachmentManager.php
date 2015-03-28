<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Gaufrette\Filesystem;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;

/**
 * Class EmailAttachmentManager
 *
 * @package Oro\Bundle\EmailBundle\Manager
 */
class EmailAttachmentManager
{
    const ATTACHMENT_DIR = 'attachment';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var string
     */
    protected $attachmentDir;

    /**
     * @var ServiceLink
     */
    protected $securityFacadeLink;

    /**
     * @var EmailActivityListProvider
     */
    protected $activityListProvider;

    /**
     * @param FileSystemMap             $filesystemMap
     * @param EntityManager             $em
     * @param KernelInterface           $kernel
     * @param ServiceLink               $securityFacadeLink
     * @param EmailActivityListProvider $activityListProvider
     */
    public function __construct(
        FilesystemMap $filesystemMap,
        EntityManager $em,
        KernelInterface $kernel,
        ServiceLink $securityFacadeLink,
        EmailActivityListProvider $activityListProvider
    ) {
        $this->filesystem           = $filesystemMap->get('attachments');
        $this->em                   = $em;
        $this->attachmentDir        = $kernel->getRootDir() . DIRECTORY_SEPARATOR . self::ATTACHMENT_DIR;
        $this->securityFacadeLink   = $securityFacadeLink;
        $this->activityListProvider = $activityListProvider;
    }

    /**
     * @param EmailAttachment $emailAttachment
     * @param object $entity
     */
    public function linkEmailAttachmentToTargetEntity(EmailAttachment $emailAttachment, $entity)
    {
        $this->cpEmailAttachmentsToFile([$emailAttachment]);
        $this->linkAttachmentsToEntities([$emailAttachment], [$entity]);
    }

    /**
     * @param Email $email
     */
    public function linkEmailAttachmentsToTargetEntities(Email $email)
    {
        $entities = $this->activityListProvider->getTargetEntities($email);
        $this->cpEmailAttachmentsToFile($email->getEmailBody()->getAttachments());
        $this->linkAttachmentsToEntities($email->getEmailBody()->getAttachments(), $entities);
    }

    /**
     * @param EmailAttachment[] $emailAttachments
     * @param array $entities
     */
    protected function linkAttachmentsToEntities($emailAttachments, $entities)
    {
        $doFlush = false;
        foreach ($entities as $entity) {
            $entityClass = ClassUtils::getClass($entity);
            foreach ($emailAttachments as $emailAttachment) {
                /** @var EmailAttachment $emailAttachment */
                if (!$emailAttachment->getFile()) {
                    continue;
                }
                $attachment = new Attachment();
                if (!$attachment->supportTarget($entityClass)) {
                    continue;
                }
                $attachment->setFile($emailAttachment->getFile());
                $attachment->setTarget($entity);
                $this->em->persist($attachment);
                $doFlush = true;
            }
        }

        if ($doFlush) {
            $this->em->flush();
        }
    }

    /**
     * @param array $emailAttachments
     */
    protected function cpEmailAttachmentsToFile($emailAttachments)
    {
        foreach ($emailAttachments as $emailAttachment) {
            $file = new File();
            $file->setExtension($emailAttachment->getExtension());
            $file->setOriginalFilename($emailAttachment->getFileName());
            $file->setMimeType($emailAttachment->getContentType());
            $file->setFilename(uniqid() . '.' . $file->getExtension());

            $content = ContentDecoder::decode(
                $emailAttachment->getContent()->getContent(),
                $emailAttachment->getContent()->getContentTransferEncoding()
            );

            $this->filesystem->write($file->getFilename(), $content);

            $f = new ComponentFile($this->getAttachmentFullPath($file->getFilename()));
            $file->setFile($f);
            $file->setFileSize($f->getSize());
            $file->setUploaded(false);

            $file->setOwner($this->securityFacadeLink->getService()->getLoggedUser());

            $emailAttachment->setFile($file);
        }
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function getAttachmentFullPath($path)
    {
        return $this->attachmentDir . '/' . $path;
    }
}
