<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command\Cron;

use Oro\Bundle\EmailBundle\Tests\Functional\EmailFeatureTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailBodySyncCommandTest extends WebTestCase
{
    use EmailFeatureTrait;

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
        $this->disableEmailFeature();
        $result = $this->runCommand('oro:cron:email-body-sync');

        $this->assertContains('The email feature is disabled. The command will not run.', $result);
    }

    /**
     * @return array
     */
    public function paramProvider()
    {
        return [
            'should show help' => [
                '$expectedContent' => 'Usage: oro:cron:email-body-sync [options]',
                '$params'          => ['--help'],
            ],
            'should show success message' => [
                '$expectedContent' => 'All emails was processed',
                '$params'          => [],
            ],
        ];
    }
}
