<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command;

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
        $result = $this->runCommand('oro:email:body-sync', $params);

        if ('' === $expectedContent) {
            $this->assertEmpty($result);
        } else {
            $this->assertContains($expectedContent, $result);
        }
    }

    public function testCommandOutputWithEmailFeatureDisabled()
    {
        $this->toggleEmailFeatureValue(false);
        $result = $this->runCommand('oro:email:body-sync', ['--id=1']);

        $this->assertContains('The email feature is disabled. The command will not run.', $result);
    }

    /**
     * Disable email feature toggle
     *
     * @param bool $value
     */
    protected function toggleEmailFeatureValue($value)
    {
        $configManager = $this->getContainer()->get('oro_config.scope.global');

        $configManager->set(Configuration::getConfigKeyByName('feature_enabled'), (bool) $value);
        $configManager->flush();
    }

    /**
     * @return array
     */
    public function paramProvider()
    {
        return [
            'should show help' => [
                '$expectedContent' => "Usage:\n  oro:email:body-sync [options]",
                '$params'          => ['--help'],
            ],
            'should show failed message for no id' => [
                '$expectedContent' => 'The identifier id is missing for a query of Oro\Bundle\EmailBundle\Entity\Email',
                '$params'          => [],
            ],
            'should show no message for non existing id' => [
                '$expectedContent' => '',
                '$params'          => ['--id=1000'],
            ],
        ];
    }
}
