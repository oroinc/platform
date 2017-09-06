<?php

namespace Oro\Bundle\InstallerBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
use Symfony\Component\Console\Exception\RuntimeException;

class FeatureContext extends OroFeatureContext implements KernelAwareContext, OroPageObjectAware
{
    use KernelDictionary, PageObjectDictionary;

    /**
     * @BeforeScenario
     */
    public function beforeScenario()
    {
        $this->getSession()->resizeWindow(1920, 1080, 'current');
    }

    /**
     * @Given /^I fill (Configuration) form according to my (parameters.yml)$/
     */
    public function iFillConfigurationAccordingToMyParametersYml()
    {
        /** @var Form $form */
        $form = $this->createElement('OroForm');
        $table = new TableNode([
            ['Password', $this->getParameter('database_password')]
        ]);
        $form->fill($table);
    }

    /**
     * @Given /^wait for (?P<initialization>(.*)) finish$/
     */
    public function waitForInit($initialization)
    {
        switch ($initialization) {
            case 'Database initialization':
                $time = 1000;
                break;
            case 'Installation':
                $time = 280;
                break;
            default:
                throw new RuntimeException('Unknown initialization');
        }

        $result = $this->spin(function (FeatureContext $context) {
            if (null !== $context->getPage()->find('css', '.icon-no')) {
                throw new RuntimeException('Error was happen during initialization');
            }
            return
                null === $context->getPage()->find('css', '.icon-wait')
                && !$context->getPage()->findLink('Next')->hasClass('disabled');
        }, $time);

        self::assertNotNull($result, sprintf('"%s" step does not fit in %s seconds', $initialization, $time));
    }

    /**
     * @When /^(?:|I )fill form with:$/
     */
    public function iFillFormWith(TableNode $table)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->getContainer()->get('oro_config.user');
        $configManager->set(PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, 2);
        $configManager->set(PasswordComplexityConfigProvider::CONFIG_NUMBERS, false);
        $configManager->set(PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false);
        $configManager->set(PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false);
        $configManager->set(PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false);
        $configManager->flush();

        /** @var Form $form */
        $form = $this->createElement('OroForm');
        $form->fill($table);
    }

    /**
     * @Then /^(?:|I )should see "(?P<text>(?:[^"]|\\")*)" message$/
     */
    public function assertPageContainsText($text)
    {
        $this->spin(function () use ($text) {
            $actual = $this->session->getPage()->getText();
            $actual = preg_replace('/\s+/u', ' ', $actual);
            $regex = '/'.preg_quote($text, '/').'/ui';

            return (bool) preg_match($regex, $actual);
        }, 5);

        $this->assertSession()->pageTextContains($text) ;
    }

    /**
     * @param string $name
     * @return string
     */
    private function getParameter($name)
    {
        return $this->getContainer()->getParameter($name);
    }

    /**
     * @Then I should be on the :number step
     */
    public function iShouldBeOnTheStep($number)
    {
        $activeStepElement = $this->getPage()->find('css', 'div.progress-bar li.active strong');
        self::assertNotNull($activeStepElement, 'Can\'t find active step on the page');
        self::assertEquals($number, $activeStepElement->getText());
    }

    /**
     * @When I press Launch application button
     */
    public function iPressLaunchApplicationButton()
    {
        $button = $this->getPage()->find('css', 'div.button-set a.primary');
        self::assertNotNull($button, 'Primary button is not found on the page');

        $button->click();
    }

    /**
     * @Then I should be on the admin login page
     */
    public function iShouldBeOnTheAdminLoginPage()
    {
        $windowNames = $this->getSession()->getWindowNames();
        $this->getSession()->switchToWindow($windowNames[1]);
        $urlPath = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);
        $route = $this->getContainer()->get('router')->match($urlPath);

        self::assertEquals(
            'oro_user_security_login',
            $route['_route'],
            sprintf(
                'Expected that current url "%s" will match "oro_user_security_login", but it matched to "%s"',
                $urlPath,
                $route['_route']
            )
        );
    }
}
