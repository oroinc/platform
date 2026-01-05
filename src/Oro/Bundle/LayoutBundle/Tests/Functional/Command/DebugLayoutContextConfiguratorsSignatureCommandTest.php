<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DebugLayoutContextConfiguratorsSignatureCommandTest extends WebTestCase
{
    public function testCommandOutput(): void
    {
        $result = self::runCommand('oro:debug:layout:context-configurators', [], false, true);
        self::assertStringContainsString('oro_layout.layout_context_configurator.application', $result);
        self::assertStringContainsString('oro_layout.layout_context_configurator.data', $result);
    }
}
