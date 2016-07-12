<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AssetsThemeDumpCommandTest extends WebTestCase
{
    const NAME = 'oro:assetic:dump:theme';
    const PARAMETER = 'default';

    protected function setUp()
    {
        $this->initClient();
    }

    public function testExecute()
    {
        $result = $this->runCommand(self::NAME, [self::PARAMETER]);
        $this->assertNotEmpty($result);

        $this->assertRegExp(sprintf('/%s/', self::PARAMETER), $result);
    }
}
