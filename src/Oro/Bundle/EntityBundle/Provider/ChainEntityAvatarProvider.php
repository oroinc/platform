<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Util\ClassUtils;

class ChainEntityAvatarProvider implements EntityAvatarProviderInterface
{
    /** @var EntityAvatarProviderInterface[] */
    protected $providers = [];

    /**
     * {@inheritdoc}
     */
    public function getAvatarImage($filterName, $entity)
    {
        foreach ($this->providers as $provider) {
            if ($image = $provider->getAvatarImage($filterName, $entity)) {
                return $image;
            }
        }

        throw new \RuntimeException(sprintf(
            'No avatar found for entity of type "%s"',
            ClassUtils::getClass($entity)
        ));
    }

    /**
     * @param EntityAvatarProviderInterface[] $providers
     */
    public function setProviders(array $providers)
    {
        $this->providers = $providers;
    }
}
