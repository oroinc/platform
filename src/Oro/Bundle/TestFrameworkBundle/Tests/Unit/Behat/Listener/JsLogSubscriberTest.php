<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat;

use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Mink\Mink;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Tester\Setup\Teardown;
use Oro\Bundle\TestFrameworkBundle\Behat\Listener\JsLogSubscriber;
use WebDriver\Session;

class JsLogSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider logProvider
     * @param array $logs
     * @param string $expectedContent
     */
    public function testLog(array $logs, $expectedContent)
    {
        /** @var JsLogSubscriber|\PHPUnit_Framework_MockObject_MockObject $jsLogSubscriber */
        $jsLogSubscriber = $this
            ->getMockBuilder(JsLogSubscriber::class)
            ->setConstructorArgs([new Mink(), sys_get_temp_dir()])
            ->setMethods(['getLogs', 'getUrl'])
            ->getMock();
        $jsLogSubscriber->method('getLogs')->willReturn($logs);
        $jsLogSubscriber->method('getUrl')->willReturn('example.com');
        $jsLogSubscriber->log($this->getEventMock());

        $expectedLogFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'behat_browser.log';
        $this->assertFileExists($expectedLogFile);
        $this->assertStringEqualsFile($expectedLogFile, $expectedContent);
    }

    public function testEmptyLog()
    {
        /** @var JsLogSubscriber|\PHPUnit_Framework_MockObject_MockObject $jsLogSubscriber */
        $jsLogSubscriber = $this
            ->getMockBuilder(JsLogSubscriber::class)
            ->setConstructorArgs([new Mink(), sys_get_temp_dir()])
            ->setMethods(['getLogs'])
            ->getMock();
        $jsLogSubscriber->method('getLogs')->willReturn([]);
        $jsLogSubscriber->log($this->getEventMock());

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
                    '[ERROR - 2017-07-10T19:04:21+00:00] [URL: "example.com"] '.
                        '[Feature: "Feature Example", On line: 0, Step: "Test JsLogger Mock Step"] '.
                        'Something went wrong'.PHP_EOL.
                    '[WARNING - 2017-07-10T19:04:21+00:00] [URL: "example.com"] '.
                        '[Feature: "Feature Example", On line: 0, Step: "Test JsLogger Mock Step"] '.
                        'Check your environment'.PHP_EOL
            ],
            'irregular log' => [
                'logs' => [
                    [
                        'timestamp' => 1499713461000,
                    ],
                ],
                'expected result' =>
                    '[UNKNOWN_LEVEL - 2017-07-10T19:04:21+00:00] [URL: "example.com"] '.
                        '[Feature: "Feature Example", On line: 0, Step: "Test JsLogger Mock Step"] '.
                        'UNKNOWN_MESSAGE'.PHP_EOL
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

    /**
     * @return AfterStepTested
     */
    private function getEventMock()
    {
        return new AfterStepTested(
            $this->createMock(Environment::class),
            new FeatureNode("Feature Example", null, [], null, [], null, null, null, 0),
            new StepNode('', 'Test JsLogger Mock Step', [], 0, null),
            $this->createMock(StepResult::class),
            $this->createMock(Teardown::class)
        );
    }
}
