<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Artifacts;

use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Output\Node\EventListener\EventListener;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Oro\Bundle\TestFrameworkBundle\Behat\Artifacts\PrettyArtifactsSubscriber;
use Oro\Bundle\TestFrameworkBundle\Behat\Artifacts\ScreenshotGenerator;
use PHPUnit\Framework\TestCase;
use WebDriver\Exception\UnexpectedAlertOpen;

class PrettyArtifactsSubscriberTest extends TestCase
{
    /**
     * Every step asks the browser for the cursor tracker, including the steps that expect an alert -
     * the previous text-matching exclusion list did not survive alerts raised by other steps.
     */
    public function testBeforeStepAlwaysReachesForTheBrowser(): void
    {
        $driver = $this->createMock(Selenium2Driver::class);
        $driver->expects($this->once())
            ->method('getWebDriverSession');

        $this->getSubscriber($driver)->beforeStep($this->getEvent('I should see alert with message "Leave?"'));
    }

    /**
     * @dataProvider browserFailureProvider
     */
    public function testBeforeStepSurvivesUnusableBrowser(\Throwable $failure): void
    {
        $driver = $this->createMock(Selenium2Driver::class);
        $driver->expects($this->once())
            ->method('getWebDriverSession')
            ->willThrowException($failure);

        $this->getSubscriber($driver)->beforeStep($this->getEvent('I go to Sales/Orders'));
    }

    public function browserFailureProvider(): array
    {
        return [
            'alert blocks script execution' => [
                new UnexpectedAlertOpen('unexpected alert open: {Alert text : You have unsaved changes}'),
            ],
            'session is gone' => [new \Error('Call to a member function execute() on null')],
        ];
    }

    private function getSubscriber(Selenium2Driver $driver): PrettyArtifactsSubscriber
    {
        $formatter = new NodeEventListeningFormatter(
            'pretty',
            'Prints the feature as is.',
            [],
            $this->createMock(OutputPrinter::class),
            $this->createMock(EventListener::class)
        );

        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getDriver')
            ->willReturn($driver);

        $mink = $this->createMock(Mink::class);
        $mink->expects($this->any())
            ->method('getSession')
            ->willReturn($session);

        return new PrettyArtifactsSubscriber(
            $formatter,
            $this->createMock(ScreenshotGenerator::class),
            $mink
        );
    }

    private function getEvent(string $stepText): BeforeStepTested
    {
        return new BeforeStepTested(
            $this->createMock(Environment::class),
            new FeatureNode('Feature Example', null, [], null, [], 'Feature', 'en', null, 1),
            new StepNode('When', $stepText, [], 1, 'When')
        );
    }
}
