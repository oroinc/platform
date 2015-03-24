<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;

use Gaufrette\Filesystem;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

/**
 * Class EmailAttachmentManager
 *
 * @package Oro\Bundle\EmailBundle\Manager
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailAttachmentManager
{
    /**
     * @var EmailCacheManager
     */
    protected $emailCacheManager;

    /**
     * @var AttachmentManager
     */
    protected $attachmentManager;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $attachmentDir;

    /**
     * @var ConfigFileValidator
     */
    protected $configFileValidator;

    /**
     * @var ServiceLink
     */
    protected $securityFacadeLink;

    /**
     * @param EmailCacheManager   $emailCacheManager
     * @param FileSystemMap       $filesystemMap
     * @param Registry            $registry
     * @param ConfigFileValidator $configFileValidator
     * @param KernelInterface     $kernel
     * @param ServiceLink         $securityFacadeLink
     */
    public function __construct(
        EmailCacheManager $emailCacheManager,
        FilesystemMap $filesystemMap,
        Registry $registry,
        ConfigFileValidator $configFileValidator,
        KernelInterface $kernel,
        ServiceLink $securityFacadeLink
    ) {
        $this->emailCacheManager   = $emailCacheManager;
        $this->filesystem          = $filesystemMap->get('attachments');
        $this->registry            = $registry;
        $this->configFileValidator = $configFileValidator;
        $this->attachmentDir       = $kernel->getRootDir() . DIRECTORY_SEPARATOR . 'attachment';
        $this->securityFacadeLink  = $securityFacadeLink;
    }

    /**
     * @param Email $email
     * @param       $entity
     * @throws LoadEmailBodyException
     *
     * @return array
     */
    public function linkEmailAttachmentsToEntity(Email $email, $entity)
    {
        $this->emailCacheManager->ensureEmailBodyCached($email);

        $em = $this->registry->getManager();
        $violations = [];
        $entityClass = ClassUtils::getClass($entity);
        $emailAttachments = $email->getEmailBody()->getAttachments();

        foreach ($emailAttachments as $emailAttachment) {
            $attachment = $emailAttachment->getAttachment() ?: new Attachment();
            if ($attachment->supportTarget($entityClass)) {
                if ($attachment->getId() == null) {
                    $this->createAttachmentFromDB($attachment, $emailAttachment);

                    $fileViolations = $this->configFileValidator->validate($entityClass, $attachment->getFile());
                    if ($fileViolations->count() > 0) {
                        $violations[$emailAttachment->getId()] = $this->transformViolations($fileViolations);

                        $this->filesystem->get($attachment->getFile()->getFilename())->delete();
                    } else {
                        $emailAttachment->setAttachment($attachment);
                        $em->persist($attachment);
                    }
                }
                $attachment->setTarget($entity);
            }
        }

        $em->flush();

        return $violations;
    }

    /**
     * @param Attachment      $attachment
     * @param EmailAttachment $emailAttachment
     */
    protected function createAttachmentFromDB(Attachment $attachment, EmailAttachment $emailAttachment)
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

        $attachment->setFile($file);
    }

    /**
     * @param ConstraintViolationListInterface $violations
     *
     * @return array
     */
    protected function transformViolations(ConstraintViolationListInterface $violations)
    {
        $v = [];
        /** @var $violation ConstraintViolation */
        foreach ($violations as $violation) {
            $v[] = $violation->getMessage();
        }

        return $v;
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
