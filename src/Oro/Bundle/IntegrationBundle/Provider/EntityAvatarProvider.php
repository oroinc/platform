<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;

use Oro\Bundle\EntityBundle\Provider\EntityAvatarProviderInterface;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\MagentoBundle\Entity\IntegrationAwareInterface;
use Oro\Bundle\UIBundle\Model\Image;

class EntityAvatarProvider implements EntityAvatarProviderInterface
{
    /** @var TypesRegistry */
    protected $typesRegistry;

    /** @var CacheManager */
    protected $cacheManager;

    /**
     * @param TypesRegistry $typesRegistry
     * @param CacheManager  $cacheManager
     */
    public function __construct(TypesRegistry $typesRegistry, CacheManager $cacheManager)
    {
        $this->typesRegistry = $typesRegistry;
        $this->cacheManager = $cacheManager;
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

        return new Image(
            Image::TYPE_FILE_PATH,
            ['path' => $this->cacheManager->getBrowserPath($entityChannel->getIcon(), $filterName)]
        );
    }
}
