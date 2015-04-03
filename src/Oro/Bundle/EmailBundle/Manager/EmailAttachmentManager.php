<?php

namespace Oro\Bundle\EmailBundle\Manager;

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
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

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
     * @param ConfigFileValidator       $configFileValidator
     */
    public function __construct(
        FilesystemMap $filesystemMap,
        EntityManager $em,
        KernelInterface $kernel,
        ServiceLink $securityFacadeLink,
        EmailActivityListProvider $activityListProvider,
        ConfigFileValidator $configFileValidator
    ) {
        $this->filesystem           = $filesystemMap->get('attachments');
        $this->em                   = $em;
        $this->attachmentDir        = $kernel->getRootDir() . DIRECTORY_SEPARATOR . self::ATTACHMENT_DIR;
        $this->securityFacadeLink   = $securityFacadeLink;
        $this->activityListProvider = $activityListProvider;
        $this->configFileValidator  = $configFileValidator;
    }

    /**
     * @param EmailAttachment $emailAttachment
     * @param object $entity
     */
    public function linkEmailAttachmentToTargetEntity(EmailAttachment $emailAttachment, $entity)
    {
        $this->cpEmailAttachmentsToFileSystem([$emailAttachment]);
        $this->linkAttachmentsToEntities([$emailAttachment], [$entity]);
    }

    /**
     * @param Email $email
     */
    public function linkEmailAttachmentsToTargetEntities(Email $email)
    {
        $entities = $this->activityListProvider->getTargetEntities($email);
        $this->cpEmailAttachmentsToFileSystem($email->getEmailBody()->getAttachments());
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
                $attachment = $this->getNewAttachment();
                if (!$attachment->supportTarget($entityClass)) {
                    continue;
                }
                $attachment->setFile($emailAttachment->getFile());
                $attachment->setTarget($entity);
                $fileViolations = $this->configFileValidator->validate($entityClass, $attachment->getFile());
                if ($fileViolations->count() > 0) {
                    $this->filesystem->get($attachment->getFile()->getFilename())->delete();
                    $emailAttachment->setFile(null);
                    $this->em->persist($emailAttachment);
                } else {
                    $this->em->persist($attachment);
                    $doFlush = true;
                }
            }
        }

        if ($doFlush) {
            $this->em->flush();
        }
    }

    /**
     * @param array $emailAttachments
     */
    protected function cpEmailAttachmentsToFileSystem($emailAttachments)
    {
        /** @var EmailAttachment $emailAttachment */
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

    /**
     * @return Attachment
     */
    public function getNewAttachment()
    {
        return new Attachment();
    }
}
