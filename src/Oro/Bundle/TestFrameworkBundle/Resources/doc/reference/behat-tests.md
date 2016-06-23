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
app/console oro:install  --force --drop-database --user-name=admin --user-email=admin@example.com --user-firstname=John --user-lastname=Doe --user-password=admin --organization-name=OroCRM --env=prod --sample-data=n
```

### Run tests

#### Configuration

Base configuration is located in [behat.yml.dist](../../config/behat.yml.dist).
Use it by parameter ```-c``` for use your custom config:

```bash
bin/behat -s OroUserBundle -c ~/config/behat.yml.dist
```

However you can copy ```behat.yml.dist``` to ```behat.yml``` in root of application and edit for your needs.
Every bundle that has configured suite in configuration will not be autoloaded.
See [Architecture](./behat-tests.md#architecture) reference below.

#### Configurate suite

You can manually configure suite for bundle in application behat config:
```yml
default: &default
  suites:
    AcmeDemoBundle:
      type: symfony_bundle
      bundle: AcmeDemoBundle
      contexts:
        - Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext
        - Oro\Bundle\DataGridBundle\Tests\Behat\Context\GridContext
        - Acme\DemoBundle\Tests\Behat\Context\FeatureContext
      paths:
        - src/Acme/DemoBundle/Tests/Behat/Features
```

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
vendor/bin/behat -p selenium2
```

Run tests with PhantomJs

```bash
vendor/bin/behat
```

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

For building testing suites ```Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\OroTestFrameworkExtension``` is used.
During initialization, Extension will create test suite with bundle name if any ```Tests/Behat/Features``` directory exits.
Thus, if bundle has no Features directory - no test suite would be crated for it.

If you need some specific feature steps for your bundle you should create ```Tests/Behat/Context/FeatureContext``` class.
This context will added to suite with other common contexts.
The full list of common context configured in behat configuration file under ```shared_contexts``` of ```OroTestFrameworkExtension``` definition

#### Page elements

Every Bundle can have own number of elements. All elements must be discribed in ```Resources/config/behat_elements.yml``` in way:

```yml
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

2. ```selector``` this is how selenium driver can found element on the page. By default it use css selector, but it also can use xpath:

 ```yml
    selector:
        type: xpath
        locator: //span[id='mySpan']/ancestor::form/
 ```
 
3. ```class``` namespace for element class. It must be extended from ```Oro\Bundle\TestFrameworkBundle\Behat\Element\Element```
4. ```options``` it's an array of extra options that will be set in options property of Element class

#### Feature isolation

Every feature can interact with application, perform CRUD operation and thereby the database can be modified.
So, it is why features are isolated to each other.
The isolation is reached by dumping the database and cache dir before tests execution
and restoring the cache and database after execution of each feature.

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
  And I fill "Login" form with:
      | Username | admin |
      | Password | admin |
  And I press "Log in"
  And I should be on "/"

Scenario Outline: Fail login
  Given I am on "/user/login"
  And I fill "Login" form with:
      | Username | <login>    |
      | Password | <password> |
  And I press "Log in"
  And I should be on "/user/login"
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
  Given I have 5 Cases
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
For load fixture before feature add tag with fixture file name and ```@fixture-``` prefix.

```gherkin
#package/crm/src/OroCRM/Bundle/ContactBundle/Tests/Behat/Features/contact-grid.feature
@fixture-contacts.yml
Feature: Contacts grid
```

#### Entity references

In both type of fixtures you can use references to entities.
[See Alice documentation about References](https://github.com/nelmio/alice/blob/2.x/doc/relations-handling.md#handling-relations).
You can use default references that was created by ```ReferenceRepositoryInitializer``` before tests run:

- ```@admin``` - Admin user
- ```@adminRole``` - Administrator role
- ```@organization``` - Default organization
- ```@business_unit``` - Default business unit
