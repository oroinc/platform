<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ConfigUpdateCommandTest extends WebTestCase
{
    /** @var ConfigManager */
    protected $configGlobal;

    /** @var ConfigManager */
    protected $configUser;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->configGlobal = $this->getContainer()->get('oro_config.global');
        $this->configUser = $this->getContainer()->get('oro_config.user');
    }

    /**
     * @dataProvider paramProvider
     *
     * @param string $expectedContent
     * @param array  $params
     * @param string $scope
     */
    public function testCommandOutput($expectedContent, $params, $scope)
    {
        $result = $this->runCommand('oro:logger:level', $params);

        if ($scope == 'global') {
            $disableAfter = new \DateTime('now', new \DateTimeZone('UTC'));
            $disableAfter->add(\DateInterval::createFromDateString($params[1]));

            $this->assertEquals(
                $params[0],
                $this->configGlobal->get(Configuration::getFullConfigKey(Configuration::LOGS_LEVEL_KEY))
            );

            $this->assertEquals(
                $disableAfter,
                $this->configGlobal->get(Configuration::getFullConfigKey(Configuration::LOGS_TIMESTAMP_KEY))
            );
        } elseif ($scope == 'user') {
            $disableAfter = new \DateTime('now', new \DateTimeZone('UTC'));
            $disableAfter->add(\DateInterval::createFromDateString($params[1]));

            $user = $this->getContainer()->get('doctrine')->getRepository('OroUserBundle:User')
                ->findOneBy(['email' => 'admin@example.com']);
            $this->configUser->setScopeIdFromEntity($user);

            $this->assertEquals(
                $params[0],
                $this->configUser->get(Configuration::getFullConfigKey(Configuration::LOGS_LEVEL_KEY))
            );

            $this->assertEquals(
                $disableAfter,
                $this->configUser->get(Configuration::getFullConfigKey(Configuration::LOGS_TIMESTAMP_KEY))
            );
        }

        $this->assertContains($expectedContent, $result);
    }

    /**
     * @return array
     */
    public function paramProvider()
    {
        return [
            'should show help' => [
                '$expectedContent' => "Usage:\n  oro:logger:level [options] [--] <level> <disable-after>",
                '$params'          => ['--help'],
                '$scope'           => ''
            ],
            'should show failed config update without required arguments' => [
                '$expectedContent' => "Not enough arguments (missing: \"level, disable-after\")",
                '$params'          => [],
                '$scope'           => ''
            ],
            'should show failed config update with wrong level argument' => [
                '$expectedContent' => "Wrong 'wrong_level' value for 'level' argument",
                '$params'          => ['wrong_level', '15 days'],
                '$scope'           => ''
            ],
            'should show failed config update with wrong disable-after argument' => [
                '$expectedContent' => "Value '15' for 'disable-after' argument should be valid date interval",
                '$params'          => ['debug', '15'],
                '$scope'           => ''
            ],
            'should show failed config update for non existing user' => [
                '$expectedContent' => "User with email 'nonexist@user.com' not exists.",
                '$params'          => ['debug', '15 days', '--user=nonexist@user.com'],
                '$scope'           => ''
            ],

            'should show success config update for user scope' => [
                '$expectedContent' => "Log level for user 'admin@example.com' is successfully set to 'debug'.",
                '$params'          => ['debug', '10 days', '--user=admin@example.com'],
                '$scope'           => 'user'
            ],
            'should show success config update for global scope' => [
                '$expectedContent' => "Log level for global scope is set to 'warning'.",
                '$params'          => ['warning', '15 days'],
                '$rowsCount'       => 'global'
            ],
        ];
    }
}
