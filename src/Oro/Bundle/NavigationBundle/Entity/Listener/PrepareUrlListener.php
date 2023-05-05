<?php

namespace Oro\Bundle\NavigationBundle\Entity\Listener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\NavigationBundle\Model\UrlAwareInterface;

/**
 * Listens to Entity that implements UrlAwareInterface to generate url
 */
class PrepareUrlListener
{
    public function prePersist(UrlAwareInterface $entity, LifecycleEventArgs $args)
    {
        $entity->setUrl($this->prepareUrl($args->getObjectManager(), get_class($entity), $entity->getUrl()));
    }

    public function preUpdate(UrlAwareInterface $entity, LifecycleEventArgs $args)
    {
        $entity->setUrl($this->prepareUrl($args->getObjectManager(), get_class($entity), $entity->getUrl()));
    }

    /**
     * Make sure url length is not bigger than url field's size
     *
     * @param EntityManager $em
     * @param string $className
     * @param string $url
     *
     * @return string
     */
    private function prepareUrl(EntityManager $em, $className, $url)
    {
        $metadata = $em->getClassMetadata($className);
        $urlMeta = $metadata->getFieldMapping('url');
        $length = $urlMeta['length'];

        return substr($url, 0, $length);
    }
}
