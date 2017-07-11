<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Mink\Mink;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
    private $kernelLogDir;

    /**
     * @param Mink $mink
     * @param string $kernelLogDir
     */
    public function __construct(Mink $mink, $kernelLogDir)
    {
        $this->mink = $mink;
        $this->kernelLogDir = $kernelLogDir;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterFeatureTested::AFTER  => ['log', 1000],
        ];
    }

    /**
     * Log browser console log if any
     */
    public function log()
    {
        $logs = $this->getLogs();

        if (!$logs) {
            return;
        }

        $logFile = $this->kernelLogDir.DIRECTORY_SEPARATOR.'behat_'.LogType::BROWSER.'.log';
        file_put_contents(
            $logFile,
            $this->formatLogs($logs),
            FILE_APPEND
        );
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
    private function formatLogs(array $content)
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

            $log .= sprintf("[%s - %s] %s\n", $level, $dateTime->format(DATE_ATOM), $message);
        }

        return $log;
    }
}
