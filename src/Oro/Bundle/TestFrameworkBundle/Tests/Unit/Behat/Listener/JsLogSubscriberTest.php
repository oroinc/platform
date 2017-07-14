<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat;

use Behat\Mink\Mink;
use Oro\Bundle\TestFrameworkBundle\Behat\Listener\JsLogSubscriber;

class JsLogSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider logProvider
     * @param array $logs
     * @param string $expectedContent
     */
    public function testLog(array $logs, $expectedContent)
    {
        $jsLogSubscriber = $this
            ->getMockBuilder(JsLogSubscriber::class)
            ->setConstructorArgs([new Mink(), sys_get_temp_dir()])
            ->setMethods(['getLogs'])
            ->getMock();
        $jsLogSubscriber->method('getLogs')->willReturn($logs);
        $jsLogSubscriber->log();

        $expectedLogFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'behat_browser.log';
        $this->assertFileExists($expectedLogFile);
        $this->assertStringEqualsFile($expectedLogFile, $expectedContent);
    }

    public function testEmptyLog()
    {
        $jsLogSubscriber = $this
            ->getMockBuilder(JsLogSubscriber::class)
            ->setConstructorArgs([new Mink(), sys_get_temp_dir()])
            ->setMethods(['getLogs'])
            ->getMock();
        $jsLogSubscriber->method('getLogs')->willReturn([]);
        $jsLogSubscriber->log();

        $expectedLogFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'behat_browser.log';
        $this->assertFileNotExists($expectedLogFile);
    }

    public function logProvider()
    {
        return [
            'regular log' => [
                'logs' => [
                    [
                        'timestamp' => 1499713461000,
                        'level' => 'ERROR',
                        'message' => 'Something went wrong'
                    ],
                    [
                        'timestamp' => 1499713461000,
                        'level' => 'WARNING',
                        'message' => 'Check your environment'
                    ],
                ],
                'expected result' =>
                    '[ERROR - 2017-07-10T19:04:21+00:00] Something went wrong'.PHP_EOL.
                    '[WARNING - 2017-07-10T19:04:21+00:00] Check your environment'.PHP_EOL
            ],
            'irregular log' => [
                'logs' => [
                    [
                        'timestamp' => 1499713461000,
                    ],
                ],
                'expected result' =>
                    '[UNKNOWN_LEVEL - 2017-07-10T19:04:21+00:00] UNKNOWN_MESSAGE'.PHP_EOL
            ],
        ];
    }

    /**
     * @after
     */
    public function removeLog()
    {
        $log = sys_get_temp_dir().DIRECTORY_SEPARATOR.'behat_browser.log';

        if (file_exists($log)) {
            unlink($log);
        }
    }
}
