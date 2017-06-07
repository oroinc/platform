# OroBehatExtension

## Content

- [Before You Begin](#before-you-begin)
- [Conventions](#conventions)
- [Getting Started](#cetting-started)
  - [Configuration](#configuration)
    - [Application Configuration](#application-configuration)
    - [Behat Configuration](#behat-configuration)
  - [Installation](#installing)
    - [Install dev dependencies](#install-dev-dependencies)
    - [Application Initial State](#application-initial-state)
    - [Install browser emulator](#install-browser-emulator)
  - [Test Execution](#test-execution)
    - [Run browser emulator](#run-browser-emulator)
    - [Run tests](#run-tests)
- [Architecture](#architecture)
  - [DI Containers](#di-containers)
  - [Suites autoload](#suites-autoload)
  - [Feature isolation](#feature-isolation)
- [Page Object](#page-object)
  - [Elements](#elements)
  - [Form Mappings](#form-mappings)
  - [Embedded Form Mappings](#ebedded-form-mappings)
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

## Before You Begin

The information below summarizes concepts and tools that are important for understanding and use of the test framework delivered within OroBehatExtension.

***Behavior-driven development (BDD)*** is a software development process that emerged from test-driven development (TDD).
The Behavior-driven development combines the general techniques and principles of TDD
with ideas from domain-driven design and object-oriented analysis and design to provide software development and management teams
with shared tools and a shared process to collaborate on software development.
[Read more at Wiki](https://en.wikipedia.org/wiki/Behavior-driven_development)

***Behat*** is a Behavior Driven Development framework for PHP.
[See more in behat documentation](http://docs.behat.org/en/v3.0/)

***Mink*** is an open source browser controller/emulator for web applications, developed using PHP.
[Mink documentation](http://mink.behat.org/en/latest/)

***OroElementFactory*** creates elements in contexts.
See more information about [page object pattern](http://www.seleniumhq.org/docs/06_test_design_considerations.jsp#page-object-design-pattern).

***Symfony2 Extension*** provides integration with Symfony2.
[See Symfony2 Extension documentation](https://github.com/Behat/Symfony2Extension/blob/master/doc/index.rst)

***@OroTestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension*** provides integration with Oro BAP based applications.

***Selenium2Driver*** Selenium2Driver provides a bridge for the Selenium2 (webdriver) tool.
See [Driver Feature Support](http://mink.behat.org/en/latest/guides/drivers.html)

***Selenium2*** browser automation tool with object oriented API.

***PhantomJS*** is a headless WebKit scriptable with a JavaScript API that is fast and originally supports various web standards, like DOM handling, CSS selector, JSON, Canvas, and SVG.

## Conventions

This section summarizes limitations and agreements that are important for shared test maintenance and use. 

- **Use form mapping instead of selectors in your scenarios** to keep them clear and understandable for people from both technical and nontechnical world.

  **Don't**:

  ```gherkin
      I fill in "oro_workflow_definition_form[label]" with "User Workflow Test"
      I fill in "oro_workflow_definition_form[related_entity]" with "User"
  ```
  **Do**:
  
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

- **Use menu and links to get the right pages instead of the direct page url**. See [Page element](#page-element) for more information.

  **Don't**:
  ```gherkin
      And I go to "/users"
  ```
  
  **Do**:

  ```gherkin
      And I open User Index page
  ```
- **Avoid scenario redundancy** (e.g. repeating same sequence of steps, like login, in multiple scenarios). Cover the feature with the sequential scenarios where every following scenario reuses outcomes (the states and data) prepared by their predecessors. This path was chosen because of the following benefits:

  - Faster scenario execution due to the shared user session and smart data preparation. The login action in the initial scenario opens the session that is reusable by the following scenarios. Preliminary scenraios (e.g. create) prepare data for the following scenarios (e.g. delete).
  - Feature level isolation boosts execution speed, especially in the slow test environments.
  - Minimized routine development actions (e.g. you don't have to load fixtures for every scenario; instead, you reuse the available outcomes of the previous scenarios).
  - Easily handle the application states that is difficult to emulate with data fixtures only (e.g. when adding new entity fields in the UI).

  By coupling scenarios, the ease of debugging and bug localization get sacrificed. It is difficult to debug UI features and the scenarios that happen after several preliminary scenarios. The longer the line, the harder it is to isolate the issue. See [Feature debugging](#feature-debugging) for more information. Once the issue occurs, you have to spend additional time to localize it and identify the root cause (e.g. the delete scenario may be malfunctioning vs the delete scenario may fail due to the issues in the preliminary scenario, for example, create). The good point is that the most critical actions/scenarios usually precede the less critical. Who cares about the delete if the create does not work in the first place? ;)

- **Use semantical yml fixtures**. Use only the entities that are in the bundle you are testing. Any other entities should be included via import. See [Alice fixtures](#alice-fixtures)

- **Name elements in camelCase style without spaces**. You can still refer to it using the camelCase style with spaces in the behat scenarios. For example, an element named ```OroProductForm``` may be mentioned in the step of the scenario as "Oro Product From":
  
  ```
  I fill "Oro Product From" with:
  ```

- **Use ```Scenario: Feature Background``` instead of the Background step**

## Getting Started

### Configuration

#### Application Configuration

Use default configuration for the application installed in production mode.
If you don't have any mail server configured locally, set ```mailer_transport``` setting in *parameters.yml* to ```null```.

#### Behat Configuration

Base configuration is located in [behat.yml.dist](../../config/behat.yml.dist).
Every application has its own behat.yml.dist file in the root of the application directory.
Create your ```behat.yml```(it is ignored by git automatically and is never commited to the remote repository), import base configuration and change it to fit your environment:

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

### Installation

#### Install dev dependencies

If you installed dependencies with ```--no-dev``` parameter earlier, remove ```composer.lock``` file from the root of the application directory.

Install dev dependencies using the following command:

```bash
composer install
```

#### Application Initial State

In Oro application, initial state is the one application enters after installation without demo data.

Scenarios that test features should rely on this state and should create any data that is necessary for additional verifications.

Data may be created by the steps of the sceario or as [fixtures](#fixtures).

Install application without demo data in production mode using the following command:

```bash
app/console oro:install  --drop-database --user-name=admin --user-email=admin@example.com \
  --user-firstname=John --user-lastname=Doe --user-password=admin \
  --organization-name=ORO --env=prod --sample-data=n --timeout=3000
```

#### Install Browser for Test Automation

To execute scenarios that use Oro application features, run browser-automation server (Selenium Web Driver) or browser (PhantomJs).
PhantomJs is more efficient but is headless and is not observable for a human. However, you can make screenshots when anything goes wrong. Selenium server runs feature tests in a real browser.

To install PhantomJs, run the following commands:

```bash
mkdir $HOME/phantomjs
wget https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-2.1.1-linux-x86_64.tar.bz2 -O $HOME/phantomjs/phantomjs-2.1.1-linux-x86_64.tar.bz2
tar -xvf $HOME/phantomjs/phantomjs-2.1.1-linux-x86_64.tar.bz2 -C $HOME/phantomjs
sudo ln -s $HOME/phantomjs/phantomjs-2.1.1-linux-x86_64/bin/phantomjs /usr/bin/phantomjs
```

**Note:** These commands create a subdirector for phantomjs in your home directory, downloads phantomjs into directory that you just created, uncompress files, creates symbolic link.

After the command execution is complete, you can use ```phantomjs``` in terminal.

To install Selenium Web Driver, use the following commands:

```bash
mkdir $HOME/selenium-server-standalone-3.4.0
curl -L https://selenium-release.storage.googleapis.com/3.4/selenium-server-standalone-3.4.0.jar > $HOME/selenium-server-standalone-3.4.0/selenium.jar
```

Next, download Chrome Driver from http://chromedriver.storage.googleapis.com/index.html?path=2.29/ and save it into your bin directory as selenium.

### Test Execution

#### Prerequisites

Run PhantomJs:

```bash
phantomjs --webdriver=8643 > /tmp/phantomjs.log 2>&1
``` 
OR

Run Standalone Selenium Server:

```bash
java -Dwebdriver.gecko.driver=/usr/local/bin/chromedriver -jar $HOME/selenium-server-standalone-2.52.0/selenium.jar -log /tmp/webdriver.log > /tmp/webdriver_output.txt 2>&1
```

> To run PhantomJs or Selenium server in background, append ampersand symbol (&) to the end of line, like in the following examples:
> ```bash
> phantomjs --webdriver=8643 > /dev/null 2>&1 &
>
> java -Dwebdriver.gecko.driver=/usr/local/bin/chromedriver -jar $HOME/selenium-server-standalone-2.52.0/selenium.jar > /dev/null 2>&1 &
> ```

#### Run tests

Before you begin, it is highly recommended to make yourself familiar with behat arguments and options. Run ```bin/behat --help``` for detailed description.

When the Oro application is installed without demo data and is running, and the PhantomJs or Selenium Server is running, you can start runing the behat tests by feature from the root of application.

You may use one of the following commands.

Run feature test scenarios with PhantomJs:

```bash
bin/behat vendor/oro/platform/src/Oro/Bundle/UserBundle/Tests/Behat/Features/login.feature -vvv
```

Run feature test scenarios with Selenium:

```bash
bin/behat vendor/oro/platform/src/Oro/Bundle/UserBundle/Tests/Behat/Features/login.feature -vvv -p selenium2
```

Preview all available feature steps:

```bash
bin/behat -dl -s OroUserBundle
```

View steps with full description and examples:

```bash
bin/behat -di -s OroUserBundle
```

Every bundle has its dedicated test suite that can be run separately:

```bash
bin/behat -s OroUserBundle
```

## Architecture

### DI Containers

You may understand the difference with two containers - Application Container and Behat Container.
Behat is a symfony console application with it's own container and services.

Behat container can be configured through Extensions by behat.yml in the root of application.
Application container can be used by inject Kernel in your Context by implementing ```KernelAwareContext``` and using ```KernelDictionary``` trait.

```php
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class FeatureContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    public function useContainer()
    {
        $doctrine = $this->getContainer()->get('doctrine');
    }
}
```

Even more, you can inject application services in behat Context:
```yml
oro_behat_extension:
  suites:
    OroCustomerAccountBridgeBundle:
      contexts:
        - OroImportExportBundle::ImportExportContext:
            - '@oro_entity.entity_alias_resolver'
            - '@oro_importexport.processor.registry'
```


### Suites autoload

For building testing suites ```Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension``` is used.
During initialization, Extension will create test suite with bundle name if any ```Tests/Behat/Features``` directory exits.
Thus, if bundle has no Features directory - no test suite would be created for it.

If you need some specific feature steps for your bundle you should create ```AcmeDemoBundle\Tests\Behat\Context\FeatureContext``` class.
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
Every Bundle can have own number of elements. All elements must be discribed in ```{BundleName}/Tests/Behat/behat.yml``` in a way:

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

1. ```Login``` is an element name that MUST be unique.
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
We will run the script via a systemd service.
Therefore you need two files: the script and the .service file (unit configuration file).

1. Create bash script in the home directory mysql_copy_tmpfs.sh:
```bash
#!/bin/bash
cp -Rfp /var/lib/mysql /var/tmpfs
```

2. Unit file /etc/systemd/system/mysql_copy_tmpfs.service:
```unit
[Unit]
Description=Copy mysql to tmpfs
Before=mysql.service
After=mount.target

[Service]
User=mysql
Type=oneshot
ExecStart=/bash/script/path/mysql_copy_tmpfs.sh

[Install]
WantedBy=multi-user.target

```

3. Once you're done with the files, enable the service:
```bash
systemctl enable mysql_copy_tmpfs.service
```

It should start automatically after rebooting the machine.
For more details see systemd.service man page.

### Couldn't generate random unique value for Oro\Bundle\UserBundle\Entity\User: username in 128 tries.

Hot fix.
Check your fixture.
Remove  (unique) suffix in entity property in entity fixture. e.g.

```yaml
Oro\Bundle\UserBundle\Entity\User:
    charlie:
      firstName: Marge
      lastName: Marge Simpson
      username (unique): marge228
```
replace
```yaml
Oro\Bundle\UserBundle\Entity\User:
    charlie:
      firstName: Marge
      lastName: Marge Simpson
      username: marge228
```


Why it happens?
Alice remember all the values for given entity property and try generate unique value, but value just one.
This feature may be useful while using with Faker:

replace
```yaml
Oro\Bundle\UserBundle\Entity\User:
    charlie:
      firstName (unique): <firstName()>
      lastName: Marge Simpson
      username: marge228
```


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

![PhpStorm step suggestion](./doc/images/phpstorm_step_suggestion.png)

> If you don't have suggestion in storm, check for:
>
> 1. You have installed vendors at list for one application
> 2. You have installed behat plugin for PhpStorm

#### Find right Context

Every Context class should implement ```Behat\Behat\Context\Context``` interface.
You can get the list of implemented contexts and find the right one by name.

![Context implements interface](./doc/images/context_implements_interface.png)
![Find context file](./doc/images/find_context.png)

Usually the name of context is self explained, e.g. GridContext, FormContext, ACLContext etc.

#### Grep in console

If for some reasons you not use PhpStorm or behat plugin, you still can find the right step by special behat console suggestion.
Just type in your console:

```bash
bin/behat -dl -s AcmeDemoBundle | grep "flash message"
```

![Grep flash messages in console](./doc/images/grep_flash_messages.png)

```bash
bin/behat -dl -s AcmeDemoBundle | grep "grid"
```

![Grep flash messages in console](./doc/images/grep_grid.png)

> You still should have installed application for using behat command line interface

## ToDo

- Separate this README to multipage document
- Explain "wait for ajax" flow
