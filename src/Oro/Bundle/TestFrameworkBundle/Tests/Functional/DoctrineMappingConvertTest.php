<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;
use Oro\Component\Testing\TempDirExtension;

/**
 * @group regression
 */
class DoctrineMappingConvertTest extends WebTestCase
{
    use CommandTestingTrait;
    use TempDirExtension;

    protected function setUp(): void
    {
        $this->markTestSkipped('Skipped due to BAP-21072');
        $this->initClient();
    }

    public function testDoctrineMappingConvertDoesNotFailed(): void
    {
        $tmpDir = $this->getTempDir('doctrine_mapping_convert');
        $commandTester = $this->doExecuteCommand(
            'doctrine:mapping:convert',
            ['to-type' => 'yaml', 'dest-path' => $tmpDir]
        );
        $this->assertSuccessReturnCode($commandTester);
    }
}
