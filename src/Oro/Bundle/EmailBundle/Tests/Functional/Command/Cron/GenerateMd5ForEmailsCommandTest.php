<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command\Cron;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateWithTranslationsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

class GenerateMd5ForEmailsCommandTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;
    use CommandTestingTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailTemplateWithTranslationsData::class]);
    }

    public function testExecute(): void
    {
        $commandTester = $this->doExecuteCommand('oro:email:generate-md5');

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, 'default_template:fd59a055a31bf807723b55396d1b1398');
    }
}
