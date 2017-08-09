<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Listener;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Suite\GenericSuite;
use Behat\Testwork\Tester\Result\IntegerTestResult;
use Behat\Testwork\Tester\Setup\SuccessfulTeardown;
use Oro\Bundle\TestFrameworkBundle\Behat\Listener\FeatureDurationSubscriber;

class FeatureDurationSubscriberTest extends \PHPUnit_Framework_TestCase
{
    protected static $logDir;

    public function setUp()
    {
        self::$logDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'logs';
        mkdir(self::$logDir);
    }

    public function testGetSubscribedEvents()
    {
        $events = FeatureDurationSubscriber::getSubscribedEvents();
        foreach ($events as $event) {
            $this->assertTrue(method_exists(FeatureDurationSubscriber::class, $event[0]));
        }
    }

    /**
     * It is expected that feature and log stored in different places
     * but common directories will cut from path in report
     * e.g. /tmp/project/logs/report.json and /tmp/project/src/feature.file
     * in report will included path without /tmp/project/ part
     */
    public function testLog()
    {
        $subscriber = new FeatureDurationSubscriber(self::$logDir);
        $features = [
            'src'.DIRECTORY_SEPARATOR.'feature1.feature',
            'src'.DIRECTORY_SEPARATOR.'feature2.feature',
            'src'.DIRECTORY_SEPARATOR.'feature3.feature',
        ];

        foreach ($features as $feature) {
            $feature = sys_get_temp_dir().DIRECTORY_SEPARATOR.$feature;
            $subscriber->setStartTime();
            $event = new AfterFeatureTested(
                new InitializedContextEnvironment(new GenericSuite(null, [])),
                new FeatureNode('', '', [], null, [], null, null, $feature, null),
                new IntegerTestResult(0),
                new SuccessfulTeardown()
            );
            $subscriber->measureFeatureDurationTime($event);
        }

        $subscriber->createReport();

        $logFile = self::$logDir.DIRECTORY_SEPARATOR.'feature_duration.json';
        $this->assertFileExists($logFile);

        $log = json_decode(file_get_contents($logFile), true);
        foreach ($log as $feature => $duration) {
            $this->assertTrue(
                in_array($feature, $features),
                sprintf('Expect that "%s" will be in report, but it is not', $feature)
            );
            $this->assertInternalType('integer', $duration);
        }
    }

    protected function tearDown()
    {
        array_map('unlink', glob(self::$logDir.'/*'));
        if (is_dir(self::$logDir)) {
            rmdir(self::$logDir);
        }
    }
}
