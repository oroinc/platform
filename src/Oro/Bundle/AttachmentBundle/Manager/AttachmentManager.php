<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;
use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\AttachmentBundle\Exception\InvalidAttachmentEncodedParametersException;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Component\PhpUtils\Formatter\BytesFormatter;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * General methods of working with attachments
 */
class AttachmentManager
{
    /**
     * @deprecated since 1.10. Use Oro\Bundle\AttachmentBundle\Manager\FileManager::READ_BATCH_SIZE instead
     */
    const READ_COUNT = FileManager::READ_BATCH_SIZE;

    const ATTACHMENT_FILE_ROUTE = 'oro_attachment_file';

    const DEFAULT_IMAGE_WIDTH = 100;
    const DEFAULT_IMAGE_HEIGHT = 100;
    const SMALL_IMAGE_WIDTH = 32;
    const SMALL_IMAGE_HEIGHT = 32;
    const THUMBNAIL_WIDTH  = 110;
    const THUMBNAIL_HEIGHT = 80;

    /** this constant is used as a replacement of empty file name to avoid an error during URL generation */
    const UNKNOWN_FILE_NAME = 'unknown.png';

    /** @var FileManager */
    private $fileManager;

    /** @var RouterInterface */
    protected $router;

    /** @var array */
    protected $fileIcons;

    /** @var AssociationManager */
    protected $associationManager;

    /** @var bool */
    protected $debug;

    /** @var bool */
    protected $debugImages;

    /**
     * @param RouterInterface    $router
     * @param array              $fileIcons
     * @param AssociationManager $associationManager
     * @param bool               $debug
     * @param bool               $debugImages
     */
    public function __construct(
        RouterInterface $router,
        $fileIcons,
        AssociationManager $associationManager,
        $debug,
        $debugImages
    ) {
        $this->router = $router;
        $this->fileIcons = $fileIcons;
        $this->associationManager = $associationManager;
        $this->debug = $debug;
        $this->debugImages = $debugImages;
    }

    /**
     * @param FileManager $fileManager
     */
    public function setFileManager(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Get attachment url
     *
     * @param object $parentEntity
     * @param string $fieldName
     * @param File   $entity
     * @param string $type
     * @param bool   $absolute
     * @return string
     */
    public function getFileUrl($parentEntity, $fieldName, File $entity, $type = 'get', $absolute = false)
    {
        return $this->getAttachment(
            ClassUtils::getRealClass($parentEntity),
            $parentEntity->getId(),
            $fieldName,
            $entity,
            $type,
            $absolute
        );
    }

    /**
     * Get url of REST API resource which can be used to get the content of the given file
     *
     * @param int    $fileId           The id of the File object
     * @param string $ownerEntityClass The FQCN of an entity the File object belongs
     * @param mixed  $ownerEntityId    The id of an entity the File object belongs
     *
     * @return string
     */
    public function getFileRestApiUrl($fileId, $ownerEntityClass, $ownerEntityId)
    {
        return $this->router->generate(
            'oro_api_get_file',
            [
                'key' => $this->buildFileKey($fileId, $ownerEntityClass, $ownerEntityId),
                '_format' => 'binary'
            ]
        );
    }

    /**
     * Get human readable file size
     *
     * @param integer $bytes
     * @return string
     */
    public function getFileSize($bytes)
    {
        return BytesFormatter::format($bytes);
    }

    /**
     * Get attachment url
     *
     * @param string $parentClass
     * @param int    $parentId
     * @param string $fieldName
     * @param File   $entity
     * @param string $type
     * @param bool   $absolute
     * @return string
     */
    public function getAttachment(
        $parentClass,
        $parentId,
        $fieldName,
        File $entity,
        $type = 'get',
        $absolute = false
    ) {
        $urlString = str_replace(
            '/',
            '_',
            base64_encode(
                implode(
                    '|',
                    [
                        $parentClass,
                        $fieldName,
                        $parentId,
                        $type,
                        $entity->getOriginalFilename()
                    ]
                )
            )
        );
        return $this->router->generate(
            static::ATTACHMENT_FILE_ROUTE,
            [
                'codedString' => $urlString,
                'extension'   => $entity->getExtension()
            ],
            $absolute ? RouterInterface::ABSOLUTE_URL : RouterInterface::ABSOLUTE_PATH
        );
    }

    /**
     * Return url parameters from encoded string
     *
     * @param $urlString
     * @return array
     *   - parent class
     *   - field name
     *   - entity id
     *   - download type
     *   - original filename
     * @throws InvalidAttachmentEncodedParametersException
     */
    public function decodeAttachmentUrl($urlString)
    {
        if (!($decodedString = base64_decode(str_replace('_', '/', $urlString)))
            || count($result = explode('|', $decodedString)) < 5
        ) {
            throw new InvalidAttachmentEncodedParametersException(
                'Attachment parameters cannot be decoded'
            );
        }

        return $result;
    }

    /**
     * Get resized image url
     *
     * @param File        $entity
     * @param int         $width
     * @param int         $height
     * @param bool|string $referenceType
     *
     * @return string
     */
    public function getResizedImageUrl(
        File $entity,
        $width = self::DEFAULT_IMAGE_WIDTH,
        $height = self::DEFAULT_IMAGE_HEIGHT,
        $referenceType = RouterInterface::ABSOLUTE_PATH
    ) {
        return $this->router->generate(
            'oro_resize_attachment',
            [
                'width'    => $width,
                'height'   => $height,
                'id'       => $entity->getId(),
                'filename' => $entity->getFilename() ?: self::UNKNOWN_FILE_NAME
            ],
            $referenceType
        );
    }

    /**
     * Get filetype icon
     *
     * @param FileExtensionInterface $entity
     * @return string
     */
    public function getAttachmentIconClass(FileExtensionInterface $entity)
    {
        return isset($this->fileIcons[$entity->getExtension()])
            ? $this->fileIcons[$entity->getExtension()]
            : $this->fileIcons['default'];
    }

    /**
     * Get image attachment link with liip imagine filter applied to image
     *
     * @param File   $entity
     * @param string $filterName
     * @return string
     */
    public function getFilteredImageUrl(File $entity, $filterName)
    {
        return $this->generateUrl(
            'oro_filtered_attachment',
            [
                'id'       => $entity->getId(),
                'filename' => $entity->getFilename() ?: self::UNKNOWN_FILE_NAME,
                'filter'   => $filterName
            ]
        );
    }

    /**
     * Generate url for prod env (without prefix "/index_dev.php")
     *
     * @param string $name
     * @param array $parameters
     * @return string
     */
    protected function generateUrl($name, $parameters = [])
    {
        if (!$this->debug || $this->debugImages) {
            return $this->router->generate($name, $parameters);
        }

        $routerContext = $this->router->getContext();
        $prevBaseUrl = $routerContext->getBaseUrl();
        $baseUrlWithoutFrontController = preg_replace('/\/[\w_]+\.php$/', '', $prevBaseUrl);
        $routerContext->setBaseUrl($baseUrlWithoutFrontController);

        $url = $this->router->generate($name, $parameters);

        $routerContext->setBaseUrl($prevBaseUrl);

        return $url;
    }

    /**
     * Builds the key of the File object
     *
     * @param int    $fileId           The id of the File object
     * @param string $ownerEntityClass The FQCN of an entity the File object belongs
     * @param mixed  $ownerEntityId    The id of an entity the File object belongs
     *
     * @return string
     */
    public function buildFileKey($fileId, $ownerEntityClass, $ownerEntityId)
    {
        return str_replace(
            '/',
            '_',
            base64_encode(serialize([$fileId, $ownerEntityClass, $ownerEntityId]))
        );
    }

    /**
     * Extracts data from the given key of the File object
     *
     * @param string $key
     *
     * @return array [fileId, ownerEntityClass, ownerEntityId]
     *
     * @throws \InvalidArgumentException
     */
    public function parseFileKey($key)
    {
        $decoded = base64_decode(str_replace('_', '/', $key));
        if ($decoded) {
            $result = @unserialize($decoded);
            if (!empty($result) && count($result) === 3) {
                return $result;
            }
        }

        throw new \InvalidArgumentException(sprintf('Invalid file key: "%s".', $key));
    }

    /**
     * Returns the list of fields responsible to store attachment associations
     *
     * @return array [target_entity_class => field_name]
     */
    public function getAttachmentTargets()
    {
        return $this->associationManager->getAssociationTargets(
            AttachmentScope::ATTACHMENT,
            $this->associationManager->getSingleOwnerFilter('attachment'),
            RelationType::MANY_TO_ONE
        );
    }

    /**
     * Check if content type is an image
     *
     * @param string $contentType
     * @return bool
     */
    public function isImageType($contentType)
    {
        return in_array(
            $contentType,
            ['image/gif','image/jpeg','image/pjpeg','image/png'],
            true
        );
    }

    /**
     * @return array
     */
    public function getFileIcons()
    {
        return $this->fileIcons;
    }
}
