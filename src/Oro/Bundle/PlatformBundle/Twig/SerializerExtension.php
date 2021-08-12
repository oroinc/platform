<?php

namespace Oro\Bundle\PlatformBundle\Twig;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\Twig\SerializerExtension as BaseSerializerExtension;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * This version of JMS serializer Twig extension that does not initializes JMS serializer service on each web request.
 */
class SerializerExtension extends BaseSerializerExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($object, $type = 'json', SerializationContext $context = null): string
    {
        if (null === $this->serializer) {
            $this->serializer = $this->getSerializer();
        }

        return parent::serialize($object, $type, $context);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'jms_serializer' => SerializerInterface::class,
        ];
    }

    private function getSerializer(): SerializerInterface
    {
        return $this->container->get('jms_serializer');
    }
}
