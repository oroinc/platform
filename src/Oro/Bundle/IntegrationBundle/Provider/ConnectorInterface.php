<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

interface ConnectorInterface extends ConnectorTypeInterface, StepExecutionAwareInterface
{
    const SYNC_DIRECTION_PULL = 'pull';
    const SYNC_DIRECTION_PUSH = 'push';
}
