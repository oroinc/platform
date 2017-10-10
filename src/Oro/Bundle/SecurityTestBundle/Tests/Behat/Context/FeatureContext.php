<?php

namespace Oro\Bundle\SecurityTestBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use WebDriver\Exception\UnexpectedAlertOpen;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @var array
     */
    private $foundXSS = [];

    /**
     * @When /^(?:|I )should not see XSS at any page of "(?P<value>(?:[^"]|\\")*)"$/
     * @param string $value
     */
    public function checkPagesForXSS($value)
    {
        $value = str_replace(' ', '_', $value);
        try {
            /** @var array $value */
            $value = Yaml::parse(file_get_contents(sprintf(__DIR__ . '/../Features/Fixtures/%s.yml', $value)));

            foreach ($value['urls'] as $url) {
                if (is_array($url)) {
                    $routeParameters = $url['parameters'] ?? [];
                    $url = $this->getUrl($url['route'], $routeParameters);
                }
                try {
                    $this->visitPath($url);
                } catch (UnexpectedAlertOpen $exception) {
                    $this->foundXSS[] = $exception->getMessage();
                }
            }
            if (!empty($this->foundXSS)) {
                self::fail('XSS was not expected');
            }
        } catch (ParseException $e) {
            printf('Unable to parse the YAML string: %s', $e->getMessage());
        }
    }

    /**
     * @param string $route
     * @param array|null $parameters
     * @return string
     */
    protected function getUrl($route, $parameters = [])
    {
        return $this->getContainer()->get('router')->generate($route, $parameters);
    }
}
