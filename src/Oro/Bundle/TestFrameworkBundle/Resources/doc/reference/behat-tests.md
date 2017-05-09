# Behat Tests

### Before you start

***Behavior-driven development (BDD)*** is a software development process that emerged from test-driven development (TDD).
Behavior-driven development combines the general techniques and principles of TDD 
with ideas from domain-driven design and object-oriented analysis and design to provide software development and management teams 
with shared tools and a shared process to collaborate on software development. [Read more at Wiki](https://en.wikipedia.org/wiki/Behavior-driven_development)

***Behat*** is a Behavior Driven Development framework for PHP. [See more at behat documentation](http://docs.behat.org/en/v3.0/)

***Mink*** is an open source browser controller/emulator for web applications, written in PHP. [Mink documentation](http://mink.behat.org/en/latest/)


***OroElementFactory*** create elements in contexts. See more about [page object pattern](http://www.seleniumhq.org/docs/06_test_design_considerations.jsp#page-object-design-pattern).

***Symfony2 Extension*** provides integration with Symfony2. [See Symfony2 Extension documentation](https://github.com/Behat/Symfony2Extension/blob/master/doc/index.rst)

***@OroTestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension*** provides integration with Oro BAP based applications. 

***Selenium2Driver*** Selenium2Driver provides a bridge for the Selenium2 (webdriver) tool. See [Driver Feature Support](http://mink.behat.org/en/latest/guides/drivers.html)

***Selenium2*** browser automation tool with object oriented API. 

***PhantomJS*** is a headless WebKit scriptable with a JavaScript API. 
It has fast and native support for various web standards: DOM handling, CSS selector, JSON, Canvas, and SVG.

### Installing

Remove ```composer.lock``` file if you install dependencies with ```--no-dev``` parameter before.

Install dev dependencies:

```bash
composer install
```

Install application without fixture in prod mode:

```bash
app/console oro:install  --drop-database --user-name=admin --user-email=admin@example.com --user-firstname=John --user-lastname=Doe --user-password=admin --organization-name=ORO --env=prod --sample-data=n
```

### Run tests

#### Configuration

Base configuration is located in [behat.yml.dist](../../config/behat.yml.dist).
However you can copy ```behat.yml.dist``` to ```behat.yml``` in root of application and edit for your needs.
Also check ```base_url``` setting, for example for crm-enterprise application by default it is ```http://dev-crm-enterprise.local/``` and in your system it may differ.

#### Run browser emulator

For execute features you need browser emulator demon (Selenium2 or PhantomJs) runing.
PhantomJs works faster but you can't view how it works in real browser.
Selenium2 server run features in firefox browser

Install PhantomJs:

```bash
mkdir $HOME/phantomjs
wget https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-2.1.1-linux-x86_64.tar.bz2 -O $HOME/phantomjs/phantomjs-2.1.1-linux-x86_64.tar.bz2
tar -xvf $HOME/phantomjs/phantomjs-2.1.1-linux-x86_64.tar.bz2 -C $HOME/phantomjs
sudo ln -s $HOME/phantomjs/phantomjs-2.1.1-linux-x86_64/bin/phantomjs /usr/bin/phantomjs
```

This commands accomplishes a number of things:

1. Created dir for phantomjs in your home directory
2. Download phantomjs into directory that you just created
3. Uncompress files
4. Created symbolic link. Now you can use ```phantomjs``` in terminal

Run PhantomJs:

```bash
phantomjs --webdriver=8643 > /tmp/phantomjs.log 2>&1
```

Install Selenium2

```bash
mkdir $HOME/selenium-server-standalone-2.52.0
curl -L http://selenium-release.storage.googleapis.com/2.52/selenium-server-standalone-2.52.0.jar > $HOME/selenium-server-standalone-2.52.0/selenium.jar
```

Install Firefox v 39.0.3

```bash
wget sourceforge.net/projects/ubuntuzilla/files/mozilla/apt/pool/main/f/firefox-mozilla-build/firefox-mozilla-build_39.0.3-0ubuntu1_amd64.deb
sudo dpkg -i firefox-mozilla-build/firefox-mozilla-build_39.0.3-0ubuntu1_amd64.deb
rm firefox-mozilla-build/firefox-mozilla-build_39.0.3-0ubuntu1_amd64.deb
```

Run Selenium2:

```bash
java -jar $HOME/selenium-server-standalone-2.52.0/selenium.jar -log /tmp/webdriver.log > /tmp/webdriver_output.txt 2>&1
```

> For run emulator in background add ampersand symbol (&) to the end of line:
> ```bash
> phantomjs --webdriver=8643 > /tmp/phantomjs.log 2>&1 &
> ```
> and
> ```bash
> java -jar $HOME/selenium-server-standalone-2.52.0/selenium.jar -log /tmp/webdriver.log > /tmp/webdriver_output.txt 2>&1 &
> ```

#### Run tests

Run tests with Selenium and Firefox:

```bash
bin/behat -p selenium2
```

Run tests with PhantomJs

```bash
bin/behat
```

#### Fail tests

If in some kind of reasons some of your tests was fail, you can view
**screenshots** at {application_root}/app/logs/

Use ```behat -v``` parameter to behat command, add get more details in verbose output

### Architecture

Mink provide ```MinkContext``` with basic feature steps.
```OroMainContext``` is extended from ```MinkContext``` and add many additional steps for features. 
```OroMainContext``` it's a shared context that added to every test suite that haven't it's own FeatureContext

To look the all available feature steps:

```bash
bin/behat -dl -s OroUserBundle
```

or view steps with full description and examples: 

```bash
bin/behat -di -s OroUserBundle
```

Every bundle has its own test suite and can be run separately:

 ```bash
 bin/behat -s OroUserBundle
 ```

#### Suites autoload

For building testing suites ```Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension``` is used.
During initialization, Extension will create test suite with bundle name if any ```Tests/Behat/Features``` directory exits.
Thus, if bundle has no Features directory - no test suite would be created for it.

If you need some specific feature steps for your bundle you should create ```Tests/Behat/Context/FeatureContext``` class.
This context will added to suite with other common contexts.
The full list of common context configured in behat configuration file under ```shared_contexts``` see [behat.yml.dist](../../config/behat.yml.dist#L21-L25)

You can manually configure suite for bundle in application behat config:

```yml
default: &default
  suites:
    AcmeDemoBundle:
      type: symfony_bundle
      bundle: AcmeDemoBundle
      contexts:
        - Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext
        - OroDataGridBundle::GridContext
        - AcmeDemoBundle::FeatureContext
      paths:
        - 'vendor/Acme/DemoBundle/Tests/Behat/Features'
```

Or in bundle behat configuration ```{BundleName}/Tests/Behat/behat.yml```:

```yml
oro_behat_extension:
  suites:
    AcmeDemoBundle:
      contexts:
        - Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext
        - OroDataGridBundle::GridContext
        - AcmeDemoBundle::FeatureContext
      paths:
        - '@AcmeDemoBundle/Tests/Behat/Features'
```

Every bundle that has configured suite in configuration file will not be autoloaded by extension.

#### Elements

Every Bundle can have own number of elements. All elements must be discribed in ```{BundleName}/Tests/Behat/behat.yml``` in way:

```yml
oro_behat_extension:
  elements:
    Login:
      selector: '#login-form'
      class: 'Oro\Bundle\TestFrameworkBundle\Behat\Element\Form'
      options:
        mapping:
          Username: '_username'
          Password: '_password'
```

1. ```Login``` is an element name. It must be unique.
 Element can be created in context by ```OroElementFactory``` by it's name:
 
 ```php
    $this->elementFactory->createElement('Login')
 ```

2. ```selector``` this is how selenium driver can found element on the page. By default it use [css selector](http://mink.behat.org/en/latest/guides/traversing-pages.html#css-selector), but it also can use xpath:

 ```yml
    selector:
        type: xpath
        locator: //span[id='mySpan']/ancestor::form/
 ```
 
3. ```class``` namespace for element class. It must be extended from ```Oro\Bundle\TestFrameworkBundle\Behat\Element\Element```
You can omnit class, if so ```Oro\Bundle\TestFrameworkBundle\Behat\Element\Element``` will use by default.
4. ```options``` it's an array of extra options that will be set in options property of Element class
5. For the forms you can, and obviously should, add mapping option.
   It will increase test speed and map form more accurately.

#### Form Mappings

By default for mapping forms [named field selector](http://mink.behat.org/en/latest/guides/traversing-pages.html#named-selectors) used, that search form by field by its id, name, label or placeholder.
You free to use any of selectors for form mappings, as well as wrap element into concrete behat element

behat.yml
```yml
oro_behat_extension:
  elements:
    Payment Method Config Type Field:
      class: Oro\Bundle\PaymentBundle\Tests\Behat\Element\PaymentMethodConfigType
      selector:
        type: 'xpath'
        locator: '//div[@id[starts-with(.,"uniform-oro_payment_methods_configs_rule_method")]]'
    Payment Rule Form:
      selector: "form[id^='oro_payment_methods_configs_rule']"
      class: Oro\Bundle\TestFrameworkBundle\Behat\Element\Form
      options:
        mapping:
          Method:
            type: 'xpath'
            locator: '//div[@id[starts-with(.,"uniform-oro_payment_methods_configs_rule_method")]]'
            element: Payment Method Config Type Field
```
Now you should implement Element ```setValue``` method

```php
<?php
namespace Oro\Bundle\PaymentBundle\Tests\Behat\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
class PaymentMethodConfigType extends Element
{
    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $values = is_array($value) ? $value : [$value];
        foreach ($values as $item) {
            $parentField = $this->getParent()->getParent()->getParent()->getParent();
            $field = $parentField->find('css', 'select');
            self::assertNotNull($field, 'Select payment method field not found');
            $field->setValue($item);
            $parentField->clickLink('Add');
            $this->getDriver()->waitForAjax();
        }
    }
}
```

Now you can just use it in standard step:
```gherkin
Feature: Payment Rules CRUD
  Scenario: Creating Payment Rule
    Given I login as administrator
    And I go to System/ Payment Rules
    And I click "Create Payment Rule"
    When I fill "Payment Rule Form" with:
      | Method | PayPal |
```

#### Ebedded Form Mappings

It's common happens that form appears in iframe.
Behat can switch to iframe by it's id.
For the appropriate filling the form in iframe you should to specify iframe id in form options:
```yml
oro_behat_extension:
  elements:
    Magento contact us form:
      selector: 'div#page'
      class: Oro\Bundle\TestFrameworkBundle\Behat\Element\Form
      options:
        embedded-id: embedded-form
        mapping:
          First name: 'oro_magento_contactus_contact_request[firstName]'
          Last name: 'oro_magento_contactus_contact_request[lastName]'
```

#### Page element

Page element encapsulate whole web page with it's url and path to this page.
Every Page element should extends from ```Oro\Bundle\TestFrameworkBundle\Behat\Element\Page```
Typical Page config is looks like:
```yml
oro_behat_extension:
  pages:
    User Profile View:
      class: Oro\Bundle\UserBundle\Tests\Behat\Page\UserProfileView
      route: 'oro_user_profile_view'
```
And Page class:
```php
<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class UserProfileView extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $userMenu = $this->elementFactory->createElement('UserMenu');
        $userMenu->find('css', 'i.fa-sort-desc')->click();

        $userMenu->clickLink('My User');
    }
}
```

#### Feature isolation

Every feature can interact with application, perform CRUD operation and thereby the database can be modified.
So, it is why features are isolated to each other.
The isolation is reached by dumping the database and cache dir before tests execution
and restoring the cache and database after execution of each feature.
Every isolator must implement ```Oro\Bundle\TestFrameworkBundle\Behat\Isolation\IsolatorInterface``` and ```oro_behat.isolator``` tag with priority.
See [TestFrameworkBundle/Behat/ServiceContainer/config/isolators.yml](../../../Behat/ServiceContainer/config/isolators.yml)

#### Disable feature isolation

You can disable feature isolation by adding ```--skip-isolators=database,cache``` option to behat console command
In this case feature should run much faster, but you should care by yourself about database and cache consistency.

### Write your first feature

Every bundle should hold its own features at ```{BundleName}/Tests/Behat/Features/``` directory
Every feature it's a single file with ```.feature``` extension and some specific syntax (See more at [Cucumber doc reference](https://cucumber.io/docs/reference))

A feature has three basic elements—the Feature: keyword, a name (on the same line) 
and an optional (but highly recommended) description that can span multiple lines.

A scenario is a concrete example that illustrates a business rule. It consists of a list of steps.
In addition to being a specification and documentation, a scenario is also a test. 
As a whole, your scenarios are an executable specification of the system.

A step typically starts with ***Given***, ***When*** or ***Then***. 
If there are multiple Given or When steps underneath each other, you can use ***And*** or ***But***. 
Cucumber does not differentiate between the keywords, but choosing the right one is important for the readability of the scenario as a whole.

Get look at login.feature in OroUserBundle - [UserBundle/Tests/Behat/Features/login.feature](../../../../UserBundle/Tests/Behat/Features/login.feature)

```gherkin
Feature: User login
  In order to login in application
  As an OroCRM admin
  I need to be able to authenticate

Scenario: Success login
  Given I am on "/user/login"
  When I fill "Login Form" with:
      | Username | admin |
      | Password | admin |
  And I press "Log in"
  Then I should be on "/"

Scenario Outline: Fail login
  Given I am on "/user/login"
  When I fill "Login Form" with:
      | Username | <login>    |
      | Password | <password> |
  And I press "Log in"
  Then I should be on "/user/login"
  And I should see "Invalid user name or password."

  Examples:
  | login | password |
  | user  | pass     |
  | user2 | pass2    |
```

1. ```Feature: User login``` starts the feature and gives it a title.
2. Behat does not parse the next 3 lines of text. (In order to... As an... I need to...). 
These lines simply provide context to the people reading your feature, 
and describe the business value derived from the inclusion of the feature in your software.
3. ```Scenario: Success login``` starts the scenario, 
and contains a description of the scenario.
4. The next 6 lines are the scenario steps, each of which is matched to a regular expression defined in Context. 
5. ```Scenario Outline: Fail login``` starts the next scenario. Scenario Outlines allow express examples through the use of a template with placeholders
 The Scenario Outline steps provide a template which is never directly run. A Scenario Outline is run once for each row in the Examples section beneath it (except for the first header row).
 Think of a placeholder like a variable. It is replaced with a real value from the Examples: table row, where the text between the placeholder angle brackets matches that of the table column header. 

### Feature fixtures

Every time when behat run new feature, application state will reset to default. (See [Feature isolation](./behat-tests.md#feature-isolation))
This mean that there are only one admin user, organization, business unit and default roles in database.
Your feature must based on data that has application after oro:install command.
But this is not enough in most cases.
Thereby you have two ways to get more data in the system - inline fixtures and alice fixtures.

#### Inline fixtures

You can create any number of any entities right in the feature.
```FixtureContext``` will guess entity class, create necessary number of objects and fill required fields, that was not specified, by [faker](https://github.com/fzaninotto/faker).
You can use [faker](https://github.com/fzaninotto/faker) and and [entity references](./behat-tests.md#entity-references) in inline fixtures.

```yml
  Given the following contacts:
    | First Name | Last Name | Email     |
    | Joan       | Anderson  | <email()> |
    | Craig      | Bishop    | <email()> |
    | Jean       | Castillo  | <email()> |
    | Willie     | Chavez    | <email()> |
    | Arthur     | Fisher    | <email()> |
    | Wanda      | Ford      | <email()> |
  And I have 5 Cases
  And there are 5 calls
  And there are two users with their own 7 Accounts
  And there are 3 users with their own 3 Tasks
  And there is user with its own Account
```

#### Alice fixtures

Sometimes you need create too much different entities with complex relationships.
In such cases you can use alice fixtures.
Alice is a library that allows you easily create fixtures in yml format.
See [Alice Documentation](https://github.com/nelmio/alice/blob/2.x/README.md).

Fixtures should be located in ```{BundleName}/Tests/Behat/Features/Fixtures``` directory.
For load fixture before feature add tag with fixture file name and ```@fixture-``` prefix e.g. ```@fixture-mass_action.yml```

```gherkin
#package/crm/src/Oro/Bundle/CRMBundle/Tests/Behat/Features/mass_delete.feature
@fixture-mass_action.yml
Feature: Mass Delete records
```

Also it is possible to load fixtures for other bundles using shortcut syntax ```@fixture-OroOrganizationBundle:BusinessUnit.yml```

```gherkin
#package/platform/src/Oro/Bundle/WorkflowBundle/Tests/Behat/Features/workflow-with-attributes.feature
@fixture-OroUserBundle:user.yml
@fixture-OroOrganizationBundle:BusinessUnit.yml
Feature: Adding attributes for workflow transition
```

#### Entity references

In both type of fixtures you can use references to entities.
[See Alice documentation about References](https://github.com/nelmio/alice/blob/2.x/doc/relations-handling.md#handling-relations).
You can use default references that was created by ```ReferenceRepositoryInitializer``` before tests run:

- ```@admin``` - Admin user
- ```@adminRole``` - Administrator role
- ```@organization``` - Default organization
- ```@business_unit``` - Default business unit
