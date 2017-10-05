<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Mocks;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ChainExtensionAwareInterface;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ClearerInterface;

abstract class ChainExtensionAwareClearer implements ClearerInterface, ChainExtensionAwareInterface
{
}
