<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Gaufrette\Filesystem;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

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

    /** @var AttachmentAssociationHelper */
    protected $attachmentAssociationHelper;

    /**
     * @param FileSystemMap               $filesystemMap
     * @param EntityManager               $em
     * @param KernelInterface             $kernel
     * @param ServiceLink                 $securityFacadeLink
     * @param Router                      $router
     * @param ConfigFileValidator         $configFileValidator
     * @param AttachmentAssociationHelper $attachmentAssociationHelper
     */
    public function __construct(
        FilesystemMap $filesystemMap,
        EntityManager $em,
        KernelInterface $kernel,
        ServiceLink $securityFacadeLink,
        Router $router,
        ConfigFileValidator $configFileValidator,
        AttachmentAssociationHelper $attachmentAssociationHelper
    ) {
        $this->filesystem = $filesystemMap->get('attachments');
        $this->em = $em;
        $this->attachmentDir = $kernel->getRootDir() . DIRECTORY_SEPARATOR . self::ATTACHMENT_DIR;
        $this->securityFacadeLink = $securityFacadeLink;
        $this->router = $router;
        $this->configFileValidator = $configFileValidator;
        $this->attachmentAssociationHelper = $attachmentAssociationHelper;
    }

    /**
     * Link attachment to scope
     *
     * @param EmailAttachment $emailAttachment
     * @param object $entity
     */
    public function linkEmailAttachmentToTargetEntity(EmailAttachment $emailAttachment, $entity)
    {
        if (!$emailAttachment->getFile()) {
            $file = $this->copyEmailAttachmentToFileSystem($emailAttachment);
            $errors = $this->configFileValidator->validate(ClassUtils::getClass($entity), $file);
            if ($errors->count() > 0) {
                $this->filesystem->get($file->getFilename())->delete();
                return;
            }
            $emailAttachment->setFile($file);
            $this->linkAttachmentToEntity($emailAttachment, $entity);
        }
    }

    /**
     * Validate file by target entity without saving.
     *
     * @param EmailAttachment $emailAttachment
     * @param string $className
     *
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public function validateEmailAttachmentForTargetClass(EmailAttachment $emailAttachment, $className)
    {
        if (null === $emailAttachment->getFile()) {
            $file = $this->copyEmailAttachmentToFileSystem($emailAttachment);
            $fileViolations = $this->configFileValidator->validate($className, $file);
            $this->filesystem->get($file->getFilename())->delete();
        } else {
            $file = $emailAttachment->getFile();
            $fileViolations = $this->configFileValidator->validate($className, $file);
        }

        return $fileViolations;
    }

    /**
     * Check is attached file to target entity
     *
     * @param EmailAttachment $attachment
     * @param object $target
     *
     * @return bool
     */
    public function isAttached($attachment, $target)
    {
        $targetEntityClass = ClassUtils::getClass($target);
        if ($this->attachmentAssociationHelper->isAttachmentAssociationEnabled($targetEntityClass)) {
            $attached = $this->em->getRepository('OroAttachmentBundle:Attachment')->findOneBy(
                [
                    ExtendHelper::buildAssociationName($targetEntityClass) => $target,
                    'file' => $attachment->getFile()
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
     * @param bool|string     $referenceType
     *
     * @return string
     */
    public function getResizedImageUrl(
        EmailAttachment $entity,
        $width = AttachmentManager::DEFAULT_IMAGE_WIDTH,
        $height = AttachmentManager::DEFAULT_IMAGE_HEIGHT,
        $referenceType = Router::ABSOLUTE_PATH
    ) {
        return $this->router->generate(
            'oro_resize_email_attachment',
            [
                'id'       => $entity->getId(),
                'width'    => $width,
                'height'   => $height,
            ],
            $referenceType
        );
    }

    /**
     * @param EmailAttachment $emailAttachment
     *
     * @return File|null
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

        return $file;
    }

    /**
     * @return Attachment
     */
    public function buildAttachmentInstance()
    {
        return new Attachment();
    }

    /**
     * @param EmailAttachment $emailAttachment
     * @param object $entity
     */
    protected function linkAttachmentToEntity(EmailAttachment $emailAttachment, $entity)
    {
        $entityClass = ClassUtils::getClass($entity);

        $attachment = $this->buildAttachmentInstance();
        if (!$attachment->supportTarget($entityClass)) {
            return;
        }
        $attachment->setFile($emailAttachment->getFile());
        $attachment->setTarget($entity);
        $this->em->persist($attachment);
        $this->em->flush();
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
