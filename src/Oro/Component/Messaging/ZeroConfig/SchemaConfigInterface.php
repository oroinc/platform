<?php
namespace Oro\Component\Messaging\ZeroConfig;


interface SchemaConfigInterface
{
    /**
     * @return string
     */
    public function getRouterTopicName();

    /**
     * @return string
     */
    public function getRouterQueueName();

    /**
     * @return string
     */
    public function getQueueTopicName();

    /**
     * @return string
     */
    public function getQueueQueueName($queueName = null);
}
