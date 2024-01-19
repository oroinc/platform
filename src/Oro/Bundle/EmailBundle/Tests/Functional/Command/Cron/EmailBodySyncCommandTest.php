<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command\Cron;

use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\EmailBundle\Tests\Functional\EmailFeatureTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailBodySyncCommandTest extends WebTestCase
{
    use EmailFeatureTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailData::class]);
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
            self::assertEmpty($result);
        } else {
            self::assertStringContainsString($expectedContent, $result);
        }
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
