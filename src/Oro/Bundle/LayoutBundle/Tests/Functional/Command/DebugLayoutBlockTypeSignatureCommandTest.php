<?php

namespace  Oro\Bundle\LayoutBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DebugLayoutBlockTypeSignatureCommandTest extends WebTestCase
{
    public function testCommandOutput(): void
    {
        $result = self::runCommand('oro:debug:layout:block-types', [], false, true);
        self::assertStringContainsString('js_modules_config', $result);
        self::assertStringContainsString('js_build', $result);
    }
}
