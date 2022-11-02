<?php

namespace Oro\Component\MessageQueue\Exception;

/**
 * Triggered in case when there is no subscribed processors to queue with specified topic.
 */
class TopicSubscriberNotFoundException extends \LogicException
{
}
