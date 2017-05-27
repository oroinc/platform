# Oro Behat Extension

## Content

- [Before you start](#before-you-start)
- [Conventions](#conventions)
- [Getting Started](#cetting-started)
  - [Configuring](#configuring)
    - [Application Configuration](#application-configuration)
    - [Behat Configuration](#behat-configuration)
  - [Installing](#installing)
    - [Install dev dependencies](#install-dev-dependencies)
    - [Application Initial State](#application-initial-state)
    - [Install browser emulator](#install-browser-emulator)
  - [Running tests](#running-tests)
    - [Run browser emulator](#run-browser-emulator)
    - [Run tests](#run-tests)
- [Architecture](#architecture)
  - [DI Containers](#di-containers)
  - [Suites autoload](#suites-autoload)
  - [Feature isolation](#feature-isolation)
- [Page Object](#page-object)
  - [Elements](#elements)
  - [Form Mappings](#form-mappings)
  - [Ebedded Form Mappings](#ebedded-form-mappings)
  - [Page element](#page-element)
- [Fixtures](#fixtures)
  - [Feature fixtures](#feature-fixtures)
  - [Inline fixtures](#inline-fixtures)
  - [Alice fixtures](#alice-fixtures)
  - [Entity references](#entity-references)
- [Write your first feature](#write-your-first-feature)
- [Troubleshooting](#troubleshooting)
  - [Append snippets](#append-snippets)
  - [Increase application performance](#increase-application-performance)
  - [Feature debugging](#feature-debugging)
    - [Pause feature execution](#pause-feature-execution)
  - [How to find the right step](#how-to-find-the-right-step)
    - [Auto suggestion in PhpStorm](#auto-suggestion-in-phpstorm)
    - [Find right Context](#find-right-context)
    - [Grep in console](#grep-in-console)
  - [Element not visible](#element-not-visible)

## Before you start

***Behavior-driven development (BDD)*** is a software development process that emerged from test-driven development (TDD).
Behavior-driven development combines the general techniques and principles of TDD 
with ideas from domain-driven design and object-oriented analysis and design to provide software development and management teams 
with shared tools and a shared process to collaborate on software development.
[Read more at Wiki](https://en.wikipedia.org/wiki/Behavior-driven_development)

***Behat*** is a Behavior Driven Development framework for PHP.
[See more at behat documentation](http://docs.behat.org/en/v3.0/)

***Mink*** is an open source browser controller/emulator for web applications, written in PHP.
[Mink documentation](http://mink.behat.org/en/latest/)


***OroElementFactory*** create elements in contexts.
See more about [page object pattern](http://www.seleniumhq.org/docs/06_test_design_considerations.jsp#page-object-design-pattern).

***Symfony2 Extension*** provides integration with Symfony2.
[See Symfony2 Extension documentation](https://github.com/Behat/Symfony2Extension/blob/master/doc/index.rst)

***@OroTestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension*** provides integration with Oro BAP based applications. 

***Selenium2Driver*** Selenium2Driver provides a bridge for the Selenium2 (webdriver) tool.
See [Driver Feature Support](http://mink.behat.org/en/latest/guides/drivers.html)

***Selenium2*** browser automation tool with object oriented API. 

***PhantomJS*** is a headless WebKit scriptable with a JavaScript API. 
It has fast and native support for various web standards: DOM handling, CSS selector, JSON, Canvas, and SVG.

## Conventions

- **We are not using selectors in scenarios** e.g.

  ```gherkin
      I fill in "oro_workflow_definition_form[label]" with "User Workflow Test"
      I fill in "oro_workflow_definition_form[related_entity]" with "User"
  ```

  Why we do so? Because it is not readable for end user.
  Scenarios should be understandable for people from both worlds - technical and nontechnical
  So, instead we are using form mapping:

  ```gherkin
      And I fill "Workflow Edit Form" with:
        | Name                  | User Workflow Test |
        | Related Entity        | User               |
  ```

  ```yaml
      Workflow Edit Form:
        selector: 'form[name="oro_workflow_definition_form"]'
        class: Oro\Bundle\TestFrameworkBundle\Behat\Element\Form
        options:
          mapping:
            Name: 'oro_workflow_definition_form[label]'
            Related Entity: 'oro_workflow_definition_form[related_entity]'
  ```

- **Scenario Coupling.**
  We know that it is a bad practice to have scenarios that are depends to each other.

  This has some pros:
  - Features runs much faster. You don't need login (initialize user session) each time, before each scenario
  - Isolation in feature level instead of scenario level isolation give a boost to speed, especially on slow test environments
  - Development speed. Usually don't need to care about fixtures before each scenario.
    Even more some of application state is difficult prepare with fixtures - think about adding new entity fields from UI

  and cons:
  - Debug behat ui features is hard.
    But debug last scenario in looong feature is even heavier when it depends on planty previous.
    See [Feature debuging](#feature-debuging)
  - Bug localization - you don't sure why delete scenario was broken because of create scenario is broken or not.
    However who care about delete if create not working? ;)
- **Semantical yml fixtures.**
  We are should use only that entities that bundle has.
  Any other entities should be included by import.
- **Elements should be named camelCase without spaces.**
  You can use it in feature with spaces after. e.g. element name ```OroProductForm``` and in step ```I fill "Oro Product From" with:```
- **We are not using Background step** (see scenario coupling above) but you can use ```Scenario: Feature Background``` for these purposes

## Getting Started

### Configuring

#### Application Configuration

For now the basic prod configuration for application is enough.
Take care about ```mailer_transport``` setting in parameters.yml.
If you don't have any mail server configured locally, you should set it to ```null```.

#### Behat Configuration

Base configuration is located in [behat.yml.dist](../../config/behat.yml.dist).
Every application has its own behat.yml.dist in the root of application directory.
Create your ```behat.yml```(it will be ignored by git), import base configuration and change it:

```yaml
imports:
  - ./behat.yml.dist

default: &default
    extensions: &default_extensions
        Behat\MinkExtension:
            base_url: "http://your-domain.local"

selenium2:
    <<: *default
    extensions:
        <<: *default_extensions
        Behat\MinkExtension:
            browser_name: chrome
            base_url: "http://your-domain.local/"

```

### Installing

#### Install dev dependencies

Remove ```composer.lock``` file if you install dependencies with ```--no-dev``` parameter before.

Install dev dependencies:

```bash
composer install
```

#### Application Initial State

In ORO application for initial state we suppose application after install without fixtures.
Every feature should depends on this state.
And additional data can be create by scenarios or just [fixtures](#Feature fixtures)

Install application without fixture in prod mode:

```bash
app/console oro:install  --drop-database --user-name=admin --user-email=admin@example.com \
  --user-firstname=John --user-lastname=Doe --user-password=admin \
  --organization-name=ORO --env=prod --sample-data=n --timeout=3000
```

#### Install browser emulator

For execute features you need browser emulator demon (Selenium2 or PhantomJs) runing.
PhantomJs works faster but you can't view how it works in real browser (however you will have screenshot if something went wrong)
Selenium2 server run features in a real browser

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
4. Created symbolic link. Now you can use ```phantomjs``` in terminal.

Install Selenium2

```bash
mkdir $HOME/selenium-server-standalone-3.4.0
curl -L https://selenium-release.storage.googleapis.com/3.4/selenium-server-standalone-3.4.0.jar > $HOME/selenium-server-standalone-3.4.0/selenium.jar
```

Download Chrome Driver and save in same directory as selenium http://chromedriver.storage.googleapis.com/index.html?path=2.29/

### Running tests

#### Run browser emulator

Run PhantomJs:

```bash
phantomjs --webdriver=8643 > /tmp/phantomjs.log 2>&1
```

Run Selenium2:

```bash
java -jar $HOME/selenium-server-standalone-2.52.0/selenium.jar -log /tmp/webdriver.log > /tmp/webdriver_output.txt 2>&1
```

> For run emulator in background add ampersand symbol (&) to the end of line:
> ```bash
> phantomjs --webdriver=8643 > /dev/null 2>&1 &
> ```
> and
> ```bash
> java -jar $HOME/selenium-server-standalone-2.52.0/selenium.jar > /dev/null 2>&1 &
> ```

#### Run tests

Before you go, it is highly recommended discover behat arguments and options:

```bash
bin/behat --help
```

If you already have installed application and running browser emulator you can try to run the first behat feature
from the root of application with PhantomJs:

```bash
bin/behat vendor/oro/platform/src/Oro/Bundle/UserBundle/Tests/Behat/Features/login.feature -vvv
```

Or with Selenium:

```bash
bin/behat vendor/oro/platform/src/Oro/Bundle/UserBundle/Tests/Behat/Features/login.feature -vvv -p selenium2
```

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

## Architecture

### DI Containers

You may understands the difference with two containers - Application Container and Behat Container.
Behat is a symfony console application with it's own container and services.

Behat container can be configured through Extensions by behat.yml in the root of application.
Application container can be used by inject Kernel in your Context by implementing ```KernelAwareContext``` and using ```KernelDictionary``` trait.

### Suites autoload

For building testing suites ```Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension``` is used.
During initialization, Extension will create test suite with bundle name if any ```Tests/Behat/Features``` directory exits.
Thus, if bundle has no Features directory - no test suite would be created for it.

If you need some specific feature steps for your bundle you should create ```Tests/Behat/Context/FeatureContext``` class.
This context will added to suite with other common contexts.
The full list of common context configured in behat configuration file under ```shared_contexts``` see [behat.yml.dist](../../config/behat.yml.dist#L29-L39)

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


### Feature isolation

Every feature can interact with application, perform CRUD operation and thereby the database can be modified.
So, it is why features are isolated to each other.
The isolation is reached by dumping the database and cache dir before tests execution
and restoring the cache and database after execution of each feature.
Every isolator must implement ```Oro\Bundle\TestFrameworkBundle\Behat\Isolation\IsolatorInterface``` and ```oro_behat.isolator``` tag with priority.
See [TestFrameworkBundle/Behat/ServiceContainer/config/isolators.yml](../../../Behat/ServiceContainer/config/isolators.yml)

##### Disable feature isolation

You can disable feature isolation by adding ```--skip-isolators=database,cache``` option to behat console command
In this case feature should run much faster, but you should care by yourself about database and cache consistency.

## Page Object

### Elements

Elements is a service layer in behat tests. They wrap all difficult business logic.
Take a minute to investigate base Mink [NodeElement](https://github.com/minkphp/Mink/blob/9ea1cebe3dc529ba3861d87c818f045362c40484/src/Element/NodeElement.php).
It has many public methods, not all of that is applicable for any element.
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

2. ```selector``` this is how selenium driver can found element on the page.
By default it use [css selector](http://mink.behat.org/en/latest/guides/traversing-pages.html#css-selector),
but it also can use xpath:

 ```yml
    selector:
        type: xpath
        locator: //span[id='mySpan']/ancestor::form/
 ```
 
3. ```class``` namespace for element class. It must be extended from ```Oro\Bundle\TestFrameworkBundle\Behat\Element\Element```
You can omit class, if so ```Oro\Bundle\TestFrameworkBundle\Behat\Element\Element``` will use by default.
4. ```options``` it's an array of extra options that will be set in options property of Element class
5. For the forms you can, and obviously should, add mapping option.
   It will increase test speed and map form more accurately.

### Form Mappings

By default for mapping forms [named field selector](http://mink.behat.org/en/latest/guides/traversing-pages.html#named-selectors) used,
that search fields by its id, name, label or placeholder.
You free to use any of selectors for form mappings, as well as wrap element into concrete behat element

behat.yml
```yml
oro_behat_extension:
  elements:
    Payment Method Config Type Field:
      class: Oro\Bundle\PaymentBundle\Tests\Behat\Element\PaymentMethodConfigType
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

### Ebedded Form Mappings

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

### Page element

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

Now you can use several useful steps:

```gherkin
    And I open User Profile View page
    And I should be on User Profile View page
```

## Fixtures

### Feature fixtures

Every time when behat run new feature, application state will reset to default.
(See [Feature isolation](./behat-tests.md#feature-isolation))
This mean that there are only one admin user, organization, business unit and default roles in database.
Your feature must based on data that has application after oro:install command.
But this is not enough in most cases.
Thereby you have two ways to get more data in the system - inline fixtures and alice fixtures.

### Inline fixtures

You can create any number of any entities right in the feature.
```FixtureContext``` will guess entity class, create necessary number of objects
and fill required fields, that was not specified, by [faker](https://github.com/fzaninotto/faker).
You can use [faker](https://github.com/fzaninotto/faker)
and [entity references](#Entity references) in inline fixtures.

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

### Alice fixtures

Sometimes you need create too much different entities with complex relationships.
In such cases you can use alice fixtures.
Alice is a library that allows you easily create fixtures in yml format.
See [Alice Documentation](https://github.com/nelmio/alice/blob/2.x/README.md).

Fixtures should be located in ```{BundleName}/Tests/Behat/Features/Fixtures``` directory.
For load fixture before feature add tag with fixture file name and ```@fixture-``` prefix e.g. ```@fixture-mass_action.yml```

```gherkin
@fixture-mass_action.yml
Feature: Mass Delete records
```

Also it is possible to load fixtures for other bundles using shortcut syntax ```@fixture-OroOrganizationBundle:BusinessUnit.yml```

```gherkin
@fixture-OroUserBundle:user.yml
@fixture-OroOrganizationBundle:BusinessUnit.yml
Feature: Adding attributes for workflow transition
```

Additional to alice native [including files](https://github.com/nelmio/alice/blob/a060587f3c90edd92a65c6c0d163972f49bc4e21/doc/fixtures-refactoring.md#including-files),
extension provide the way to import files from other bundles
```yaml
include:
    - OroPricingBundle::Pricelists.yml
```

**Moreover, you should always include fixtures from other bundles with entities that was declared within that bundle [see Conventions](#conventions).**

### Entity references

In both type of fixtures you can use references to entities.
[See Alice documentation about References](https://github.com/nelmio/alice/blob/2.x/doc/relations-handling.md#handling-relations).
You can use default references that was created by ```ReferenceRepositoryInitializer``` before tests run:

- ```@admin``` - Admin user
- ```@adminRole``` - Administrator role
- ```@organization``` - Default organization
- ```@business_unit``` - Default business unit

## Write your first feature

Every bundle should hold its own features at ```{BundleName}/Tests/Behat/Features/``` directory
Every feature it's a single file with ```.feature``` extension and some specific syntax
(See more at [Cucumber doc reference](https://cucumber.io/docs/reference))

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
5. ```Scenario Outline: Fail login``` starts the next scenario. 
  Scenario Outlines allow express examples through the use of a template with placeholders
  The Scenario Outline steps provide a template which is never directly run. 
  A Scenario Outline is run once for each row in the Examples section beneath it (except for the first header row).
  Think of a placeholder like a variable. 
  It is replaced with a real value from the Examples: table row, 
  where the text between the placeholder angle brackets matches that of the table column header. 

## Troubleshooting

### Increase application performance (Ubuntu)

In behat we have such isolators ([see Feature isolation](#Feature isolation)) to make behat features independent to each other.
One of that isolator is database.
It create database dump before start execution, then drop and restore from dump after each feature.
This can take a while. In modern PC it can take up to 2 minutes.
If you run behat tests often you would like to decrease this time.
To boost database isolator you can mount database directory to ram.
We will use [tmpfs](https://en.wikipedia.org/wiki/Tmpfs)

Create a tmpfs directory:

```bash
sudo mkdir /var/tmpfs
sudo mount -t tmpfs -o size=4G tmpfs /var/tmpfs
```

Edit ```/etc/mysql/mysql.conf.d/mysqld.cnf```

```ini
datadir = /var/tmpfs/mysql
```

Add new storage to ```/etc/fstab```:

```ini
tmpfs  /var/tmpfs  tmpfs  nodev,nosuid,noexec,noatime,size=4G  0 0
```

Copy MySQL to tmpfs:

```bash
sudo service mysql stop
sudo cp -Rfp /var/lib/mysql /var/tmpfs
```

We’ll need to tell AppArmor to let MySQL write to the new directory by creating an alias between the default directory and the new location.

```bash
echo "alias /var/lib/mysql/ -> /var/tmpfs/mysql," | sudo tee -a /etc/apparmor.d/tunables/alias
```

For the changes to take effect, restart AppArmor:

```bash
sudo systemctl restart apparmor
```

Now you can start mysql again:

```bash
sudo service mysql start
```

#### (optional) Create startup script

After restart all your data will lost.
However the all db structure will lost too. You should copy data dir manually every start, or create a startup script.

### Append snippets

During the feature development you have next design stages:
- Create feature draft. When you have an high level of story implementation.
  In this stage you should have clear understanding what business outcome you want to achieve.
- Specifying the scenarios of using this feature. Concrete steps is steel not needed.
- Get imagine of implementation and write down steps.
  
Some of steps will be already fully automated. We wish that all steps will be automated at once you wrote down them.
But you may still have some steps that's not.
If feature functionality is already implemented - it's the good time to write behat steps implementation.

The faster way to do so is run in console:

```bash
bin/behat path/to/your.feature --dry-run --append-snippets --snippets-type=regex
```

This is just run your feature without real execution (*--dry-run*) 
and in the end of execution ask you to add undefined steps mock implementations to one of existing contexts

### How to find the right step

During writing new feature you may have trouble with find right steps from hundred of that already automated
You can use some useful tricks to find right step for you.

#### Auto suggestion in PhpStorm

While typing in feature file some keywords, PhpStorm will suggest you implemented steps.
Just try to type some of keywords, e.g. grid or form and find step that fit to your case

![PhpStorm step suggestion](../images/phpstorm_step_suggestion.png)

> If you don't have suggestion in storm, check for:
>
> 1. You have installed vendors at list for one application
> 2. You have installed behat plugin for PhpStorm

#### Find right Context

Every Context class should implement ```Behat\Behat\Context\Context``` interface.
You can get the list of implemented contexts and find the right one by name.

![Context implements interface](../images/context_implements_interface.png)
![Find context file](../images/find_context.png)

Usually the name of context is self explained, e.g. GridContext, FormContext, ACLContext etc.

#### Grep in console

If for some reasons you not use PhpStorm or behat plugin, you still can find the right step by special behat console suggestion.
Just type in your console:

```bash
bin/behat -dl -s AcmeDemoBundle | grep "flash message"
```

![Grep flash messages in console](../images/grep_flash_messages.png)

```bash
bin/behat -dl -s AcmeDemoBundle | grep "grid"
```

![Grep flash messages in console](../images/grep_grid.png)

> You still should have installed application for using behat command line interface

## ToDo

- Separate this README to multipage document
- Explain "wait for ajax" flow
