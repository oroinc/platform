<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OroBuildCommandTest extends WebTestCase
{
    const NAME = 'oro:requirejs:build';

    protected function setUp()
    {
        $this->initClient();
    }

    public function testExecute()
    {
        $result = $this->runCommand(self::NAME);
        $this->assertNotEmpty($result);

        $pattern = 'Generating require\.js config';
        $pattern .= '\\nRunning code optimizer';
        $pattern .= '\\nCleaning up';

        $this->assertRegExp(sprintf('/%s/', $pattern), $result);
    }
}
