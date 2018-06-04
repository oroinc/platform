<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\Command;

use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class LoggerLevelCommandTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    public function testRunCommandToUpdateUserScope()
    {
        $configUser = $this->getContainer()->get('oro_config.user');
        $params = ['debug', '10 days', '--user=admin@example.com'];
        $result = $this->runCommand('oro:logger:level', $params);
        $expectedContent = "Log level for user 'admin@example.com' is successfully set to 'debug' till";

        $this->assertContains($expectedContent, $result);

        $disableAfter = new \DateTime('now', new \DateTimeZone('UTC'));
        $disableAfter->add(\DateInterval::createFromDateString($params[1]));

        $user = $this->getContainer()->get('doctrine')->getRepository('OroUserBundle:User')
            ->findOneBy(['email' => 'admin@example.com']);
        $configUser->setScopeIdFromEntity($user);

        $this->assertEquals(
            $params[0],
            $configUser->get(Configuration::getFullConfigKey(Configuration::LOGS_LEVEL_KEY))
        );

        $this->assertEquals(
            $disableAfter->getTimestamp(),
            $configUser->get(Configuration::getFullConfigKey(Configuration::LOGS_TIMESTAMP_KEY)),
            'Failed asseting that disable after is correct.',
            10
        );
    }

    public function testRunCommandToUpdateGlobalScope()
    {
        $configGlobal = $this->getContainer()->get('oro_config.global');
        $params = ['warning', '15 days'];
        $result = $this->runCommand('oro:logger:level', $params);
        $expectedContent = "Log level for global scope is set to 'warning' till";

        $this->assertContains($expectedContent, $result);

        $disableAfter = new \DateTime('now', new \DateTimeZone('UTC'));
        $disableAfter->add(\DateInterval::createFromDateString($params[1]));

        $this->assertEquals(
            $params[0],
            $configGlobal->get(Configuration::getFullConfigKey(Configuration::LOGS_LEVEL_KEY))
        );

        $this->assertEquals(
            $disableAfter->getTimestamp(),
            $configGlobal->get(Configuration::getFullConfigKey(Configuration::LOGS_TIMESTAMP_KEY)),
            'Failed asseting that disable after is correct.',
            10
        );
    }

    /**
     * @dataProvider runCommandWithFailedValidationDataProvider
     *
     * @param string      $expectedContent
     * @param array       $params
     */
    public function testRunCommandWithFailedValidation($expectedContent, $params)
    {
        $result = $this->runCommand('oro:logger:level', $params);

        $this->assertContains($expectedContent, $result);
    }

    /**
     * @return array
     */
    public function runCommandWithFailedValidationDataProvider()
    {
        return [
            'should show failed config update without required arguments' => [
                '$expectedContent' => "Not enough arguments (missing: \"level, disable-after\")",
                '$params'          => [],
            ],
            'should show failed config update with wrong level argument' => [
                '$expectedContent' => "Wrong 'wrong_level' value for 'level' argument",
                '$params'          => ['wrong_level', '15 days'],
            ],
            'should show failed config update with wrong disable-after argument' => [
                '$expectedContent' => "Value '15' for 'disable-after' argument should be valid date interval",
                '$params'          => ['debug', '15'],
            ],
            'should show failed config update for non existing user' => [
                '$expectedContent' => "User with email 'nonexist@user.com' not exists.",
                '$params'          => ['debug', '15 days', '--user=nonexist@user.com'],
            ],
        ];
    }

    public function testCommandContainsHelp()
    {
        $result = $this->runCommand('oro:logger:level', ['--help']);

        $this->assertContains("Usage: oro:logger:level [options] [--] <level> <disable-after>", $result);
    }
}
