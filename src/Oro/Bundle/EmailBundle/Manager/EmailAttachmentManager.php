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
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;

/**
 * Class EmailAttachmentManager
 *
 * @package Oro\Bundle\EmailBundle\Manager
 */
class EmailAttachmentManager
{
    const ATTACHMENT_DIR = 'attachment';

    /** @var Filesystem */
    protected $filesystem;

    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $attachmentDir;

    /** @var ServiceLink */
    protected $securityFacadeLink;

    /**
     * @param FileSystemMap             $filesystemMap
     * @param EntityManager             $em
     * @param KernelInterface           $kernel
     * @param ServiceLink               $securityFacadeLink
     * @param ConfigFileValidator       $configFileValidator
     */
    public function __construct(
        FilesystemMap $filesystemMap,
        EntityManager $em,
        KernelInterface $kernel,
        ServiceLink $securityFacadeLink,
        ConfigFileValidator $configFileValidator
    ) {
        $this->filesystem           = $filesystemMap->get('attachments');
        $this->em                   = $em;
        $this->attachmentDir        = $kernel->getRootDir() . DIRECTORY_SEPARATOR . self::ATTACHMENT_DIR;
        $this->securityFacadeLink   = $securityFacadeLink;
        $this->configFileValidator  = $configFileValidator;
    }

    /**
     * @param EmailAttachment $emailAttachment
     * @param object $entity
     */
    public function linkEmailAttachmentToTargetEntity(EmailAttachment $emailAttachment, $entity)
    {
        $this->copyEmailAttachmentToFileSystem($emailAttachment);
        $this->linkAttachmentsToEntities($emailAttachment, $entity);
    }

    /**
     * @param EmailAttachment $emailAttachment
     * @param object $entity
     */
    protected function linkAttachmentsToEntities(EmailAttachment $emailAttachment, $entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        if (!$emailAttachment->getFile()) {
            return;
        }
        $attachment = $this->buildAttachmentInstance();
        if (!$attachment->supportTarget($entityClass)) {
            return;
        }
        $attachment->setFile($emailAttachment->getFile());
        $attachment->setTarget($entity);
        $file = $attachment->getFile();
        $fileViolations = $this->configFileValidator->validate($entityClass, $file);
        if ($fileViolations->count() > 0) {
            $this->filesystem->get($file->getFilename())->delete();
            $emailAttachment->setFile(null);
            $this->em->persist($emailAttachment);
        } else {
            $this->em->persist($attachment);
        }
        $this->em->flush();
    }

    /**
     * @param EmailAttachment $emailAttachment
     */
    protected function copyEmailAttachmentToFileSystem(EmailAttachment $emailAttachment)
    {
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
    public function buildAttachmentInstance()
    {
        return new Attachment();
    }
}
