<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Mink;
use Behat\Mink\Selector\CssSelector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Dumps elements configuration to the file for further usage in the Behat tests Chrome browser extension.
 */
class ElementsDumpSubscriber implements EventSubscriberInterface
{
    private const string ELEMENTS_FILE_PATH = '/public/media/behat_tests_elements.json';
    private CssSelector $cssSelector;

    public function __construct(private Mink $mink, private array $elements, private string $projectDir)
    {
        $this->cssSelector = new CssSelector();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeFeatureTested::BEFORE => 'beforeFeature',
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function beforeFeature(BeforeFeatureTested $event): void
    {
        // if Chrome browser is in headless mode - do nothing
        $driver = $this->mink->getSession()->getDriver();
        if (!$driver instanceof Selenium2Driver) {
            return;
        }
        $browserCapabilities = $driver->getDesiredCapabilities();
        if (
            $browserCapabilities
            && isset($browserCapabilities['chromeOptions']['args'])
            && \in_array('--headless', $browserCapabilities['chromeOptions']['args'], true)
        ) {
            return;
        }

        $result = [];
        foreach ($this->elements as $elementName => $element) {
            $elementConfig = ['name' => $elementName];
            if (!array_key_exists('selector', $element)) {
                continue;
            }
            if ($element['selector']['type'] === 'css') {
                $elementConfig['css'] = $element['selector']['locator'];
                $elementConfig['xpath'] = $this->cssSelector->translateToXPath($element['selector']['locator']);
            } else {
                $elementConfig['xpath'] = $element['selector']['locator'];
            }
            $result[] = $elementConfig;
        }

        $filePath = $this->projectDir . self::ELEMENTS_FILE_PATH;
        $content = \json_encode($result);
        if (\file_exists($filePath) && \file_get_contents($filePath) === $content) {
            return;
        }
        \file_put_contents($filePath, $content);
    }
}
