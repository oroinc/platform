<?php

namespace Oro\Bundle\PlatformBundle\Twig;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\Twig\SerializerExtension as BaseSerializerExtension;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Serializer helper twig extension
 *
 * Basically provides access to JMSSerializer from Twig
 */
class SerializerExtension extends BaseSerializerExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return SerializerInterface
     */
    protected function getSerializer()
    {
        return $this->container->get('jms_serializer');
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($object, $type = 'json', SerializationContext $context = null): string
    {
        if (!$this->serializer) {
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
}
