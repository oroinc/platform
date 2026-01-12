<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Oro\Bundle\ApiBundle\Async\Topic\CreateOpenApiSpecificationTopic;
use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends a message to create OpenAPI specification to the message queue
 * after a OpenAPI specification entity is created or changed by a user.
 */
class OpenApiSpecificationEventListener
{
    private MessageProducerInterface $producer;

    public function __construct(MessageProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    public function beforeFlush(AfterFormProcessEvent $event): void
    {
        $data = $event->getData();
        if (
            $data instanceof OpenApiSpecification
            && $data->getStatus() !== OpenApiSpecification::STATUS_CREATING
            && $data->getStatus() !== OpenApiSpecification::STATUS_RENEWING
        ) {
            $data->setStatus(OpenApiSpecification::STATUS_RENEWING);
        }
    }

    public function afterFlush(AfterFormProcessEvent $event): void
    {
        $data = $event->getData();
        if ($data instanceof OpenApiSpecification) {
            $message = ['entityId' => $data->getId()];
            if ($data->getStatus() === OpenApiSpecification::STATUS_RENEWING) {
                $message['renew'] = true;
            }
            $this->producer->send(CreateOpenApiSpecificationTopic::getName(), $message);
        }
    }
}
