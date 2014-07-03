<?php

namespace Oro\Bundle\AttachmentBundle\Twig;

use Doctrine\ORM\EntityManager;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class FileExtension extends \Twig_Extension
{
    const DEFAULT_THUMB_SIZE = 16;

    const FILES_TEMPLATE = 'OroAttachmentBundle:Twig:file.html.twig';
    const IMAGES_TEMPLATE = 'OroAttachmentBundle:Twig:image.html.twig';

    /** @var AttachmentManager */
    protected $manager;

    /** @var ConfigProvider */
    protected $attachmentConfigProvider;

    protected $em;

    /**
     * @param AttachmentManager $manager
     * @param ConfigManager     $configManager
     */
    public function __construct(AttachmentManager $manager, ConfigManager $configManager, EntityManager $em)
    {
        $this->manager = $manager;
        $this->attachmentConfigProvider = $configManager->getProvider('attachment');
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_attachment_url', [$this, 'getAttachmentUrl']),
            new \Twig_SimpleFunction('oro_resized_attachment_url', [$this, 'getResizedImageUrl']),
            new \Twig_SimpleFunction('oro_filtered_attachment_url', [$this, 'getFilteredImageUrl']),
            new \Twig_SimpleFunction('oro_configured_image_url', [$this, 'getConfiguredImageUrl']),
            new \Twig_SimpleFunction('oro_attachment_icon', [$this, 'getAttachmentIcon']),
            new \Twig_SimpleFunction(
                'oro_file_view',
                [$this, 'getFileView'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new \Twig_SimpleFunction(
                'oro_image_view',
                [$this, 'getImageView'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_attachment';
    }

    /**
     * Get attachment file url
     *
     * @param object     $parentEntity
     * @param string     $fieldName
     * @param File       $attachment
     * @param string     $type
     * @param bool       $absolute
     * @return string
     */
    public function getAttachmentUrl(
        $parentEntity,
        $fieldName,
        File $attachment,
        $type = 'get',
        $absolute = false
    ) {
        return $this->manager->getAttachmentUrl($parentEntity, $fieldName, $attachment, $type, $absolute);
    }

    /**
     * Get resized attachment image url
     *
     * @param File $attachment
     * @param int        $width
     * @param int        $height
     * @return string
     */
    public function getResizedImageUrl(
        File $attachment,
        $width = self::DEFAULT_THUMB_SIZE,
        $height = self::DEFAULT_THUMB_SIZE
    ) {
        return $this->manager->getResizedImageUrl($attachment, $width, $height);
    }

    /**
     * Get attachment icon class
     *
     * @param File $attachment
     * @return string
     */
    public function getAttachmentIcon(File $attachment)
    {
        return $this->manager->getAttachmentIconClass($attachment);
    }

    /**
     * Get file view html block
     *
     * @param \Twig_Environment $environment
     * @param object|string     $parentEntity
     * @param string            $fieldName
     * @param File              $attachment
     * @return string
     */
    public function getFileView(
        \Twig_Environment $environment,
        $parentEntity,
        $fieldName,
        $attachment = null,
        $parentId = null
    ) {
        if (is_string($parentEntity) && $parentId) {
            $parentEntity = $this->em->getRepository($parentEntity)->find($parentId);
        }
        if ($attachment && $attachment->getFilename()) {
            return $environment->loadTemplate(self::FILES_TEMPLATE)->render(
                [
                    'iconClass' => $this->manager->getAttachmentIconClass($attachment),
                    'url' => $this->manager->getAttachmentUrl($parentEntity, $fieldName, $attachment, 'download', true),
                    'fileName' => $attachment->getOriginalFilename()
                ]
            );
        }

        return '';
    }

    /**
     * Get Image html block
     *
     * @param \Twig_Environment $environment
     * @param object            $parentEntity
     * @param File              $attachment
     * @param string|object     $entityClass
     * @param string            $fieldName
     * @return string
     */
    public function getImageView(
        \Twig_Environment $environment,
        $parentEntity,
        File $attachment = null,
        $entityClass = null,
        $fieldName = ''
    ) {
        if ($attachment && $attachment->getFilename()) {
            $width = self::DEFAULT_THUMB_SIZE;
            $height = self::DEFAULT_THUMB_SIZE;

            if ($entityClass && $fieldName) {
                if (is_object($entityClass)) {
                    $entityClass = ClassUtils::getRealClass($entityClass);
                }
                $config = $this->attachmentConfigProvider->getConfig($entityClass, $fieldName);
                $width = $config->get('width');
                $height = $config->get('height');
            }
            return $environment->loadTemplate(self::IMAGES_TEMPLATE)->render(
                [
                    'imagePath' => $this->manager->getResizedImageUrl($attachment, $width, $height),
                    'url' => $this->manager->getAttachmentUrl($parentEntity, $fieldName, $attachment, 'download', true),
                    'fileName' => $attachment->getOriginalFilename()
                ]
            );
        }

        return '';
    }

    /**
     * Get attachment image resized with config values
     *
     * @param object     $parentEntity
     * @param string     $fieldName
     * @param File       $attachment
     *
     * @return string
     */
    public function getConfiguredImageUrl($parentEntity, $fieldName, File $attachment = null)
    {
        if (!$attachment) {
            $attachment = PropertyAccess::createPropertyAccessor()->getValue($parentEntity, $fieldName);
        }

        if ($attachment && $attachment->getFilename()) {
            $entityClass = ClassUtils::getRealClass($parentEntity);
            $config = $this->attachmentConfigProvider->getConfig($entityClass, $fieldName);

            return $this->getResizedImageUrl($attachment, $config->get('width'), $config->get('height'));
        }

        return '';
    }

    public function getFilteredImageUrl(File $attachment, $filterName)
    {
        return $this->manager->getFilteredImageUrl($attachment, $filterName);
    }
}
