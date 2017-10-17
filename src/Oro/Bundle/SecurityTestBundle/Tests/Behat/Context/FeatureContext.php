<?php

namespace Oro\Bundle\SecurityTestBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderDictionary;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * This context test listed URLs for presence of preloaded XSS string
 */
class FeatureContext extends OroFeatureContext implements
    OroPageObjectAware,
    KernelAwareContext,
    FixtureLoaderAwareInterface
{
    use PageObjectDictionary, KernelDictionary, FixtureLoaderDictionary;

    /**
     * Store found XSS by URL, hope we will not have memory overflow here
     *
     * @var array
     */
    private $foundXss = [];

    /**
     * @When /^(?:|I )visiting pages listed in "(?P<value>(?:[^"]|\\")*)"$/
     * @param string $value
     */
    public function visitingPagesListedIn($value)
    {
        $this->foundXss = [];
        foreach ($this->getUrlsToProcess($value) as $url) {
            $this->visitPath($this->getUrl($url));
            $this->getDriver()->waitPageToLoad();
            $this->checkEntitySelectDialogsForXss();
            $this->collectXssAfterStep();
        }
    }

    /**
     * @When /^(?:|I )should not get XSS vulnerabilities$/
     */
    public function shouldNotSeeXss()
    {
        if (!empty($this->foundXss)) {
            self::fail($this->getXssReport());
        }
        $this->foundXss = [];
    }

    /**
     * Read XSS identifiers stored in window.xss JavaScript global variable. Variable is defined in XssPayloadProvider
     * @AfterStep
     */
    public function collectXssAfterStep()
    {
        $newXss = $this->getSession()->evaluateScript('return window.xss || null;');
        if ($newXss) {
            $url = $this->getSession()->getCurrentUrl();
            if (!array_key_exists($url, $this->foundXss)) {
                $this->foundXss[$url] = [];
            }
            $this->foundXss[$url] = array_merge($this->foundXss[$url], $newXss);
        }
    }

    /**
     * @return string
     */
    private function getXssReport()
    {
        $error = 'Found XSS:';
        foreach ($this->foundXss as $url => $fields) {
            $error .= PHP_EOL . $url;
            $error .= PHP_EOL . "\t" . implode(PHP_EOL . "\t", $fields);
        }
        $error .= PHP_EOL;

        return $error;
    }

    /**
     * @param array|string $url
     * @return string
     */
    private function getUrl($url): string
    {
        if (is_array($url)) {
            $routeParameters = $url['parameters'] ?? [];
            foreach ($routeParameters as &$routeParameter) {
                if (strpos($routeParameter, '@') === 0) {
                    $reference = substr($routeParameter, 1);
                    $property = null;
                    if (strpos($reference, '.') > 0) {
                        list($reference, $property) = explode('.', $reference);
                    }

                    $routeParameter = $this->fixtureLoader->getReference($reference, $property);
                }
            }
            $url = $this->getContainer()->get('router')->generate($url['route'], $routeParameters);

            return $url;
        }

        return $url;
    }

    /**
     * @param string $value
     * @return array
     */
    private function getUrlsToProcess($value)
    {
        $urls = [];
        $value = str_replace(' ', '_', $value);
        try {
            /** @var array $value */
            $value = Yaml::parse(
                file_get_contents(sprintf(__DIR__ . '/../../../Resources/config/xss/%s.yml', $value))
            );
            if (array_key_exists('urls', $value) && is_array($value['urls'])) {
                $urls = $value['urls'];
            }
        } catch (ParseException $e) {
            self::fail(printf('Unable to parse the YAML string: %s', $e->getMessage()));
        }

        return $urls;
    }

    private function checkEntitySelectDialogsForXss()
    {
        $entitySelectButtons = $this->findAllElements('Entity Select Button');
        if (count($entitySelectButtons) > 0) {
            foreach ($entitySelectButtons as $entitySelectButton) {
                $entitySelectButton->focus();
                if ($entitySelectButton->isVisible()) {
                    $entitySelectButton->click();
                    $this->getDriver()->waitForAjax();
                    $this->collectXssAfterStep();
                    $closeBtn = $this->createElement('Close Dialog Button');
                    $closeBtn->focus();
                    $closeBtn->click();
                }
            }
        }
    }
}
