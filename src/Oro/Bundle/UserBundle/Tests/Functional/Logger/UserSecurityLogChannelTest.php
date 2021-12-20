<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Logger;

use Oro\Bundle\LoggerBundle\Tests\Functional\Logger\DbLogsHandlerTestCase;

class UserSecurityLogChannelTest extends DbLogsHandlerTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getLogChannelName(): string
    {
        return 'oro_account_security';
    }
}
