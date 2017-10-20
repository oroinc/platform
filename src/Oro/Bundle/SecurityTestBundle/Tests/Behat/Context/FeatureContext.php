<?php

namespace Oro\Bundle\SecurityTestBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApplicationBundle\Tests\Behat\Context\CommerceMainContext;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FormBundle\Tests\Behat\Element\Select2Entity;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderDictionary;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use WebDriver\Exception\NoAlertOpenError;

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
     * @var CommerceMainContext
     */
    private $commerceMainContext;

    /**
     * @var OroMainContext
     */
    private $mainContext;

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
            if (empty($url['options']['disable_select2_checks'])) {
                $this->checkSelect2ForXss();
            }
            $this->collectXssAfterStep();
        }
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->commerceMainContext = $environment->getContext(CommerceMainContext::class);
        $this->mainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * @When /^(?:|I )login to admin area as fixture user "(?P<value>(?:[^"]|\\")*)"$/
     * @param string $value
     */
    public function loginAsFixtureUser($value)
    {
        $username = $this->fixtureLoader->getReference($value, 'username');
        $this->mainContext->loginAsUserWithPassword($username);
    }

    /**
     * @When /^(?:|I )login to store frontend as fixture customer user "(?P<value>(?:[^"]|\\")*)"$/
     * @param string $value
     */
    public function loginAsFixtureCustomerUser($value)
    {
        $username = $this->fixtureLoader->getReference($value, 'email');
        $this->commerceMainContext->loginAsBuyer($username);
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
     * @AfterStep
     */
    public function collectXssAfterStep()
    {
        $newXss = $this->getSession()->evaluateScript('return window._xssDataStorage || null;');
        if ($newXss) {
            $url = $this->getSession()->getCurrentUrl();
            if (!array_key_exists($url, $this->foundXss)) {
                $this->foundXss[$url] = [];
            }
            $this->foundXss[$url] = array_merge($this->foundXss[$url], $newXss);
        }
        $this->getSession()->executeScript('window._xssDataStorage = [];');
    }

    /**
     * @When /^(?:|I )set quote "(?P<quote>(?:[^"]|\\")*)" status to "(?P<status>(?:[^"]|\\")*)"$/
     * @param string $quote
     * @param string $status
     */
    public function setQuoteStatus($quote, $status)
    {
        /** @var Quote $quote */
        $quote = $this->fixtureLoader->getReference($quote);
        $enumStatusClass = ExtendHelper::buildEnumValueClassName('quote_internal_status');
        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityManager $em */
        $em = $registry->getManagerForClass($enumStatusClass);
        $statusEnum = $em->find($enumStatusClass, $status);
        $quote->setInternalStatus($statusEnum);

        $em->flush($quote);
    }

    /**
     * @return string
     */
    private function getXssReport()
    {
        $errorStr = ['Found XSS:'];
        foreach ($this->foundXss as $url => $fields) {
            $errorStr[] = $url;

            $elements = [];
            foreach ($fields as $fieldData) {
                $elements[] = "\t" . sprintf('XPath: %s; Field: %s', $fieldData['element'], $fieldData['cause']);
            }
            $errorStr = array_merge($errorStr, array_unique($elements));
        }

        return implode(PHP_EOL, $errorStr);
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

    private function checkSelect2ForXss()
    {
        /** @var Select2Entity[] $autocompletes */
        $autocompletes = $this->findAllElements('Entity Autocomplete');
        if (count($autocompletes) > 0) {
            foreach ($autocompletes as $autocomplete) {
                $dialog = $autocomplete->openSelectEntityPopup();
                if ($dialog) {
                    $dialog->close();

                    $autocomplete->getResultSet();
                    $autocomplete->close();
                }
            }
        }
    }
}
