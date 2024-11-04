<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class FullApplicationSanitizeSqlDumpTest extends WebTestCase
{
    #[\Override]
    protected function setup(): void
    {
        $this->initClient();
    }

    public function testApplicationWideSanitizeSqlDump(): void
    {
        $this->runCommand('oro:sanitize:dump-sql', [], true, true);
    }
}
