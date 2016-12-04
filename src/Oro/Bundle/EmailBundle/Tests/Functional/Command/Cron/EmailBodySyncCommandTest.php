<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command\Cron;

use Oro\Bundle\EmailBundle\DependencyInjection\Configuration;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class EmailBodySyncCommandTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData'
        ]);
    }

    protected function tearDown()
    {
        $this->toggleEmailFeatureValue(true);
    }

    /**
     * @dataProvider paramProvider
     *
     * @param string $expectedContent
     * @param array  $params
     */
    public function testCommandOutput($expectedContent, $params)
    {
        $result = $this->runCommand('oro:cron:email-body-sync', $params);

        if ('' === $expectedContent) {
            $this->assertEmpty($result);
        } else {
            $this->assertContains($expectedContent, $result);
        }
    }

    public function testCommandOutputWithEmailFeatureDisabled()
    {
        $this->toggleEmailFeatureValue(false);
        $result = $this->runCommand('oro:cron:email-body-sync', []);

        $this->assertContains('The email feature is disabled. The command will not run.', $result);
    }

    /**
     * Disable email feature toggle
     *
     * @param bool $value
     */
    protected function toggleEmailFeatureValue($value)
    {
        $key = Configuration::getConfigKeyByName('feature_enabled');

        $configManager = $this->getContainer()->get('oro_config.manager');
        $configManager->set($key, (bool) $value);
        $configManager->flush();

        $featureChecker = $this->getContainer()->get('oro_featuretoggle.checker.feature_checker');
        $featureChecker->resetCache();
    }

    /**
     * @return array
     */
    public function paramProvider()
    {
        return [
            'should show help' => [
                '$expectedContent' => "Usage:\n  oro:cron:email-body-sync [options]",
                '$params'          => ['--help'],
            ],
            'should show success message' => [
                '$expectedContent' => 'All emails was processed',
                '$params'          => [],
            ],
        ];
    }
}
