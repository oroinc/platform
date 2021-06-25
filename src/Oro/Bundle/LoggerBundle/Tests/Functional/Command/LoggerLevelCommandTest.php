<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\Command;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class LoggerLevelCommandTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testRunCommandToUpdateUserScope()
    {
        $configUser = self::getConfigManager('user');
        $params = ['debug', '10 days', '--user=admin@example.com'];
        $result = $this->runCommand('oro:logger:level', $params);
        $expectedContent = "Log level for user 'admin@example.com' is successfully set to 'debug' till";

        static::assertStringContainsString($expectedContent, $result);

        $disableAfter = new \DateTime('now', new \DateTimeZone('UTC'));
        $disableAfter->add(\DateInterval::createFromDateString($params[1]));

        $user = $this->getContainer()->get('doctrine')->getRepository('OroUserBundle:User')
            ->findOneBy(['email' => 'admin@example.com']);
        $configUser->setScopeIdFromEntity($user);

        static::assertEquals(
            $params[0],
            $configUser->get(Configuration::getFullConfigKey(Configuration::LOGS_LEVEL_KEY))
        );

        static::assertEqualsWithDelta(
            $disableAfter->getTimestamp(),
            $configUser->get(Configuration::getFullConfigKey(Configuration::LOGS_TIMESTAMP_KEY)),
            10,
            'Failed asserting that disable after is correct.'
        );
    }

    public function testRunCommandToUpdateGlobalScope()
    {
        $configGlobal = self::getConfigManager('global');
        $params = ['warning', '15 days'];
        $result = $this->runCommand('oro:logger:level', $params);
        $expectedContent = "Log level for global scope is set to 'warning' till";

        static::assertStringContainsString($expectedContent, $result);

        $disableAfter = new \DateTime('now', new \DateTimeZone('UTC'));
        $disableAfter->add(\DateInterval::createFromDateString($params[1]));

        static::assertEquals(
            $params[0],
            $configGlobal->get(Configuration::getFullConfigKey(Configuration::LOGS_LEVEL_KEY))
        );

        static::assertEqualsWithDelta(
            $disableAfter->getTimestamp(),
            $configGlobal->get(Configuration::getFullConfigKey(Configuration::LOGS_TIMESTAMP_KEY)),
            10,
            'Failed asserting that disable after is correct.'
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

        static::assertStringContainsString($expectedContent, $result);
    }

    /**
     * @return array
     */
    public function runCommandWithFailedValidationDataProvider()
    {
        return [
            'should show failed config update without required arguments' => [
                '$expectedContent' => 'Not enough arguments (missing: "level, disable-after")',
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

        static::assertStringContainsString('Usage: oro:logger:level [options] [--] <level> <disable-after>', $result);
    }
}
