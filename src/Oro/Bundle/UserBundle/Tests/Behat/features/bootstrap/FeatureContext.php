<?php
// @codingStandardsIgnoreStart
use Behat\Gherkin\Node\TableNode;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\BehatWebContext;

class FeatureContext extends BehatWebContext
{
    // @codingStandardsIgnoreEnd
    /** @var  Form */
    protected $form;

    /**
     * @Given /^Login as an existing "([^"]*)" user and "([^"]*)" password$/
     */
    public function loginAsAnExistingUserAndPassword($user, $password)
    {
        /** @var Client $client */
        $client = self::getClientInstance();
        $header = \Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI::generateBasicHeader($user, $password);
        //open default route
        $client->request('GET', $client->generate('oro_default'), array(), array(), $header);
        \Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI::assertJsonResponse($client->getResponse(), 200, false);
        PHPUnit_Framework_Assert::assertContains('Dashboard', $client->getCrawler()->html());
    }

    /**
     * @Given /^I open "([^"]*)" dialog$/
     */
    public function iOpenDialog($dialog)
    {
        $client = self::getClientInstance();
        $route = 'oro_' . str_replace(' ', '_', strtolower($dialog));
        $client->request('GET', $client->generate($route));
        \Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI::assertJsonResponse($client->getResponse(), 200, false);
        PHPUnit_Framework_Assert::assertContains(
            'Create User - Users - Users Management - System',
            $client->getCrawler()->html()
        );
    }

    /**
     * @When /^I fill in user form:$/
     */
    public function iFillInUserForm(TableNode $userTable)
    {
        $client = self::getClientInstance();
        $this->form = $client->getCrawler()->selectButton('Save and Close')->form();
        //transform parameters
        foreach ($userTable->getHash() as $userHash) {
            $this->fillForm($this->form, $userHash['FIELD'], $userHash['VALUE']);
        }
    }

    /**
     * @param Form $form
     * @param string $field
     * @param mixed $value
     */
    protected function fillForm($form, $field, $value)
    {
        switch (strtolower($field)) {
            case 'enabled':
                $form['oro_user_user_form[enabled]'] = (int)$value;
                break;
            case 'username':
                $form['oro_user_user_form[username]'] = $value;
                break;
            case 'password':
                $form['oro_user_user_form[plainPassword][first]'] = $value;
                $form['oro_user_user_form[plainPassword][second]'] = $value;
                break;
            case 'first name':
                $form['oro_user_user_form[firstName]'] = $value;
                break;
            case 'last name':
                $form['oro_user_user_form[lastName]'] = $value;
                break;
            case 'email':
                $form['oro_user_user_form[email]'] = $value;
                break;
            case 'roles':
                switch (strtolower($value)) {
                    case 'user':
                        $form['oro_user_user_form[roles][0]'] = 2;
                        break;
                }
                break;
        }
    }

    /**
     * @Given /^I press "([^"]*)"$/
     */
    public function iPress($button)
    {
        $client = self::getClientInstance();
        $client->followRedirects();
        $client->submit($this->form);

        \Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI::assertJsonResponse($client->getResponse(), 200, false);
    }

    /**
     * @Then /^I should see "([^"]*)"$/
     */
    public function iShouldSee($message)
    {
        $client = self::getClientInstance();
        $this->assertContains($message, $client->getCrawler()->html());
    }
}
