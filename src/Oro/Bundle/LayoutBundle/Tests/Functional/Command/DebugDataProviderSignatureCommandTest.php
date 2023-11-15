<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Yaml\Yaml;

class DebugDataProviderSignatureCommandTest extends WebTestCase
{
    public function testCommandOutput(): void
    {
        $output = self::runCommand('oro:debug:layout:data-providers', [], false, true);

        $array = Yaml::parse($output);
        $this->assertIsArray($array);
        foreach ($array as $providerInfo) {
            $this->assertArrayHasKey('class', $providerInfo);
            $this->assertArrayHasKey('methods', $providerInfo);
        }
    }
}
