<?php

namespace Oro\Bundle\AttachmentBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class PlaceholderFilter
{
    /**
     * @var ConfigProvider
     */
    protected $attachmentConfigProvider;

    /**
     * @param ConfigProvider $attachmentConfigProvider
     */
    public function __construct(ConfigProvider $attachmentConfigProvider)
    {
        $this->attachmentConfigProvider = $attachmentConfigProvider;
    }

    /**
     * Checks if the entity can has notes
     *
     * @param object $entity
     * @return bool
     */
    public function isAttachmentAssociationEnabled($entity)
    {
        if (null === $entity || !is_object($entity)) {
            return false;
        }

        $className = ClassUtils::getClass($entity);

        return
            $this->attachmentConfigProvider->hasConfig($className)
            && $this->attachmentConfigProvider->getConfig($className)->is('enabled');
    }
}
