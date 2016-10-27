<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityAvatarProviderInterface;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\MagentoBundle\Entity\IntegrationAwareInterface;
use Oro\Bundle\UIBundle\Model\Image;

class EntityAvatarProvider implements EntityAvatarProviderInterface
{
    /** @var TypesRegistry */
    protected $typesRegistry;

    /**
     * @param TypesRegistry $typesRegistry
     */
    public function __construct(TypesRegistry $typesRegistry)
    {
        $this->typesRegistry = $typesRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvatarImage($filterName, $entity)
    {
        if (!$entity instanceof IntegrationAwareInterface || !$entity->getChannel()) {
            return null;
        }

        $entityChannelType = $entity->getChannel()->getType();
        $channelTypes = $this->typesRegistry->getRegisteredChannelTypes();

        if (!$channelTypes->containsKey($entityChannelType)) {
            return null;
        }

        $entityChannel = $channelTypes->get($entityChannelType);
        if (!$entityChannel instanceof IconAwareIntegrationInterface) {
            return null;
        }

        return new Image(Image::TYPE_FILE_PATH, ['path' => $entityChannel->getIcon()]);
    }
}
