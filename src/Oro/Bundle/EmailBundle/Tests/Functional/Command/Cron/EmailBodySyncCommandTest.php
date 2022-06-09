<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command\Cron;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailBodySyncCommandTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailData::class]);
    }

    private static function getFeatureChecker(): FeatureChecker
    {
        return self::getContainer()->get('oro_featuretoggle.checker.feature_checker');
    }

    public function testExecute(): void
    {
        $result = $this->runCommand('oro:cron:email-body-sync');
        self::assertStringContainsString('All emails was processed', $result);
    }

    public function testHelp(): void
    {
        $result = $this->runCommand('oro:cron:email-body-sync', ['--help']);
        self::assertStringContainsString('Usage: oro:cron:email-body-sync [options]', $result);
    }

    public function testWhenEmailFeatureDisabled()
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_email.feature_enabled', false);
        $configManager->flush();
        $featureChecker = self::getFeatureChecker();
        $featureChecker->resetCache();

        try {
            $result = $this->runCommand('oro:cron:email-body-sync');
        } finally {
            $configManager->set('oro_email.feature_enabled', true);
            $configManager->flush();
            $featureChecker->resetCache();
        }

        self::assertStringContainsString('The feature that enables this CRON command is turned off.', $result);
    }
}
