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
                $time = 120;
                break;
            default:
                throw new RuntimeException('Unknown initialization');
        }

        $this->spin(function (FeatureContext $context) {
            if (null !== $context->getPage()->find('css', '.icon-no')) {
                throw new RuntimeException('Error was happen during initialization');
            }
            return
                null === $context->getPage()->find('css', '.icon-wait')
                && !$context->getPage()->findLink('Next')->hasClass('disabled');
        }, $time);
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
}
