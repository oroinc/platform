<?php

namespace Oro\Bundle\PlatformBundle\Twig;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Twig\SerializerExtension as BaseSerializerExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SerializerExtension extends BaseSerializerExtension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    protected function getSerializer()
    {
        return $this->container->get('jms_serializer');
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($object, $type = 'json', SerializationContext $context = null)
    {
        if (!$this->serializer) {
            $this->serializer = $this->getSerializer();
        }

        return parent::serialize($object, $type, $context);
    }
}
