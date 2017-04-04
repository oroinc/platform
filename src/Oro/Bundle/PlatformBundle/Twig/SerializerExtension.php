<?php

namespace Oro\Bundle\PlatformBundle\Twig;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Twig\SerializerExtension as BaseSerializerExtension;
use Oro\Component\DependencyInjection\ServiceLink;

class SerializerExtension extends BaseSerializerExtension
{
    /** @var ServiceLink */
    protected $serializerLink;

    /**
     * @param ServiceLink $serializerLink Link is used because of performance reasons
     */
    public function __construct(ServiceLink $serializerLink)
    {
        $this->serializerLink = $serializerLink;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($object, $type = 'json', SerializationContext $context = null)
    {
        if (!$this->serializer) {
            $this->serializer = $this->serializerLink->getService();
            if (!$this->serializer) {
                throw new \RuntimeException('The JMS Serializer was not found.');
            }
        }

        return parent::serialize($object, $type, $context);
    }
}
