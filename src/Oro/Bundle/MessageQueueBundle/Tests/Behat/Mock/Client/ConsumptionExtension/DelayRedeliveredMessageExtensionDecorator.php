<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Behat\Mock\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;

/**
 * Decorate DelayRedeliveredMessageExtension to prevent creating new message during redelivery process
 */
class DelayRedeliveredMessageExtensionDecorator extends AbstractExtension
{
}
