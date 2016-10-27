<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\Provider\EntityAvatarProviderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\UIBundle\Model\Image;

class EntityAvatarProvider implements EntityAvatarProviderInterface
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /**
     * @param ConfigProvider $entityConfigProvider
     */
    public function __construct(ConfigProvider $entityConfigProvider)
    {
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvatarImage($filterName, $entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        if (!$this->entityConfigProvider->hasConfig($entityClass)) {
            return null;
        }

        $icon = $this->entityConfigProvider->getConfig($entityClass)->get('icon');
        if (!$icon) {
            return null;
        }

        return new Image(Image::TYPE_ICON, ['class' => $icon]);
    }
}
