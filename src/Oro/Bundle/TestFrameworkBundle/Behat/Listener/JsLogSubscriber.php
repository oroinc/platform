<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Mink\Mink;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WebDriver\Exception\UnknownCommand;
use WebDriver\LogType;

/**
 * Get browser console log and put in the behat_browser.log in format [LEVEL - DATE_TIME] MESSAGE
 */
class JsLogSubscriber implements EventSubscriberInterface
{
    /**
     * @var Mink
     */
    private $mink;

    /**
     * @var string
     */
    private $logDir;

    /**
     * @var array
     */
    private $savedLogs = [];

    /**
     * @var string
     */
    private $logFile;

    /**
     * @param Mink $mink
     * @param string $logDir
     */
    public function __construct(Mink $mink, $logDir)
    {
        $this->mink = $mink;
        $this->logDir = $logDir;
        $this->logFile = $this->logDir.DIRECTORY_SEPARATOR.'behat_'.LogType::BROWSER.'.log';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterStepTested::AFTER  => ['log', 1000],
            AfterFeatureTested::AFTER  => ['clear', 1000],
        ];
    }

    public function clear()
    {
        $this->savedLogs = [];
    }

    /**
     * Log browser console log if any
     */
    public function log(AfterStepTested $event)
    {
        try {
            $newLogs = $this->getLogs();
        } catch (UnknownCommand $e) {
            // get browser log is not supported by driver
            return;
        }

        if (!$newLogs) {
            return;
        }
        $logs = array_slice($newLogs, count($this->savedLogs));

        if (!$logs) {
            return;
        }

        file_put_contents(
            $this->logFile,
            $this->formatLogs($logs, $event),
            FILE_APPEND
        );

        $this->savedLogs = $newLogs;
    }

    /**
     * @return array
     */
    protected function getLogs()
    {
        /** @var OroSelenium2Driver $driver */
        $driver = $this->mink->getSession()->getDriver();

        return $driver->getWebDriverSession()->log(LogType::BROWSER);
    }

    /**
     * @param array $content array console log from WebDriver
     * @return string
     */
    protected function formatLogs(array $content, AfterStepTested $event)
    {
        $log = '';

        foreach ($content as $item) {
            $time = isset($item['timestamp'])
                ? '@'.round($item['timestamp']/1000)
                : 'now';
            $dateTime = new \DateTime($time);
            $level = isset($item['level'])
                ? $item['level']
                : 'UNKNOWN_LEVEL';
            $message = isset($item['message'])
                    ? $item['message']
                    : 'UNKNOWN_MESSAGE';
            $url = sprintf('[URL: "%s"]', $this->getUrl());
            $step = sprintf(
                '[Feature: "%s", On line: %s, Step: "%s"]',
                $event->getFeature()->getTitle(),
                $event->getStep()->getLine(),
                $event->getStep()->getText()
            );

            $log .= sprintf("[%s - %s] %s %s %s\n", $level, $dateTime->format(DATE_ATOM), $url, $step, $message);
        }

        return $log;
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return $this->mink->getSession()->getCurrentUrl();
    }
}
