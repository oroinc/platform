<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\ConfigBundle\Tests\Behat\Element\SystemConfigForm;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\FormBundle\Tests\Behat\Element\Select;
use Oro\Bundle\FormBundle\Tests\Behat\Element\Select2Entity;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\CollectionField;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FormContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Fill form with data
     * Example: And fill form with:
     *            | Subject     | Simple text     |
     *            | Users       | [Charlie, Pitt] |
     *            | Date        | 2017-08-24      |
     *
     * @When /^(?:|I )fill "(?P<formName>(?:[^"]|\\")*)" with:$/
     * @When /^(?:|I )fill form with:$/
     */
    public function iFillFormWith(TableNode $table, $formName = "OroForm")
    {
        /** @var Form $form */
        $form = $this->createElement($formName);
        $form->fill($table);
    }

    //@codingStandardsIgnoreStart
    /**
     * @When /^(?:|I )open select entity popup for field "(?P<fieldName>[\w\s]*)" in form "(?P<formName>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )open select entity popup for field "(?P<fieldName>[\w\s]*)"$/
     */
    //@codingStandardsIgnoreEnd
    public function iOpenSelectEntityPopup($fieldName, $formName = "OroForm")
    {
        /** @var Select2Entity $field */
        $field = $this->getFieldInForm($fieldName, $formName);
        $field->openSelectEntityPopup();
    }

    /**
     * @When /^(?:|I )clear "(?P<fieldName>[\w\s]*)" field in form "(?P<formName>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )clear "(?P<fieldName>[\w\s]*)" field$/
     */
    public function iClearField($fieldName, $formName = "OroForm")
    {
        /** @var ClearableInterface $field */
        $field = $this->getFieldInForm($fieldName, $formName);

        if (!$field instanceof ClearableInterface) {
            throw new \RuntimeException(sprintf(
                'Element "%s" doesn\'t have ability to clear himself. 
                Behat element "%s" must implement "%s" interface to do this',
                $fieldName,
                is_object($field) ? get_class($field) : gettype($field),
                ClearableInterface::class
            ));
        }

        $field->clear();
    }

    /**
     * @When /^(?:|I )save and close form$/
     */
    public function iSaveAndCloseForm()
    {
        $this->createOroForm()->saveAndClose();
    }

    /**
     * @When /^(?:|I )save and duplicate form$/
     */
    public function iSaveAndDuplicateForm()
    {
        $this->createOroForm()->saveAndDuplicate();
    }

    /**
     * @When /^(?:|I )(save|submit) form$/
     */
    public function iSaveForm()
    {
        $this->createOroForm()->save();
    }

    /**
     * @When /^(?:|I )save and create new form$/
     */
    public function iSaveAndCreateNewForm()
    {
        $this->createOroForm()->saveAndCreateNew();
    }

    /**
     * Find and assert field value
     * It's valid for entity edit or entity view page
     * Example: And Account Name field should has Good Company value
     * Example: And Account Name field should has Good Company value
     * Example: And Description field should has Our new partner value
     *
     * @When /^(?P<fieldName>[\w\s]*) field should has (?P<fieldValue>.+) value$/
     */
    public function fieldShouldHaveValue($fieldName, $fieldValue)
    {
        $possibleElementName = $this->fixStepArgument($fieldName);
        if ($this->elementFactory->hasElement($possibleElementName)) {
            $value = $this->createElement($possibleElementName)->getValue();
            self::assertSame($fieldValue, $value);

            return;
        }

        $page = $this->getSession()->getPage();
        $labels = $page->findAll('css', 'label');

        /** @var NodeElement $label */
        foreach ($labels as $label) {
            if (preg_match(sprintf('/%s/i', $fieldName), $label->getText())) {
                if ($label->hasAttribute('for')) {
                    return $this->getSession()
                        ->getPage()
                        ->find('css', '#' . $label->getAttribute('for'))
                        ->getValue();
                }

                $value = $label->getParent()->find('css', 'div.control-label')->getText();
                self::assertRegExp(sprintf('/%s/i', $fieldValue), $value);

                return;
            }
        }

        self::fail(sprintf('Can\'t find field with "%s" label', $fieldName));
    }

    /**
     * Find and assert that field value is empty
     *
     * @When /^(?P<fieldName>[\w\s]*) field is empty$/
     */
    public function fieldIsEmpty($fieldName)
    {
        return $this->fieldShouldHaveValue($fieldName, "");
    }

    /**
     * This is available for collection fields
     * See Emails and Phones in Contact create page
     * Example: And set "charlie@gmail.com" as primary email
     * Example: And set "+1 415-731-9375" as primary phone
     *
     * @Given /^(?:|I )set "(?P<value>[^"]+)" as primary (?P<field>[^"]+)$/
     */
    public function setFieldWithValueAsPrimary($field, $value)
    {
        /** @var CollectionField $collection */
        $collection = $this->createOroForm()->findField(ucfirst(Inflector::pluralize($field)));
        $collection->setFieldAsPrimary($value);
    }

    /**
     * Close form error message
     *
     * @Then /^(?:|I )close error message$/
     */
    public function closeErrorMessage()
    {
        $this->createOroForm()->find('css', '.alert-error button.close')->press();
    }

    /**
     * Assert that provided validation errors for given fields appeared
     * Example: Then I should see validation errors:
     *            | Subject         | This value should not be blank.  |
     * Example: Then I should see "Some Form" validation errors:
     *            | Subject         | This value should not be blank.  |
     *
     * @Then /^(?:|I )should see validation errors:$/
     * @Then /^(?:|I )should see "(?P<formName>(?:[^"]|\\")*)" validation errors:$/
     * @throws \Exception
     */
    public function iShouldSeeValidationErrors(TableNode $table, $formName = 'OroForm')
    {
        $this->waitForValidationErrorsAssertion(function () use ($table, $formName) {
            /** @var OroForm $form */
            $form = $this->createElement($formName);

            foreach ($table->getRows() as $row) {
                list($label, $value) = $row;
                $error = $form->getFieldValidationErrors($label);
                self::assertEquals(
                    $value,
                    $error,
                    "Failed asserting that $label has error $value"
                );
            }
        });
    }

    /**
     * Assert that provided validation errors for given fields appeared
     * Example: Then I should not see validation errors:
     *            | Subject         | This value should not be blank.  |
     * Example: Then I should not see "Some Form" validation errors:
     *            | Subject         | This value should not be blank.  |
     *
     * @Then /^(?:|I )should not see validation errors:$/
     * @Then /^(?:|I )should not see "(?P<formName>(?:[^"]|\\")*)" validation errors:$/
     *
     * @throws \Exception
     */
    public function iShouldNotSeeValidationErrors(TableNode $table, $formName = 'OroForm')
    {
        $this->waitForValidationErrorsAssertion(function () use ($table, $formName) {
            /** @var OroForm $form */
            $form = $this->createElement($formName);

            foreach ($table->getRows() as $row) {
                list($label, $value) = $row;
                $errors = $form->getAllFieldValidationErrors($label);
                self::assertFalse(
                    in_array($value, $errors),
                    sprintf('Failed asserting that "%s" does not contain following error "%s"', $label, $value)
                );
            }
        });
    }

    /**
     * Fill embed form
     * Example: And I fill in address:
     *            | Primary         | check         |
     *            | Country         | United States |
     *            | Street          | Selma Ave     |
     *            | City            | Los Angeles   |
     *
     * @Given /^(?:|I )fill in (?P<fieldSetLabel>[^"]+):$/
     */
    public function iFillInFieldSet($fieldSetLabel, TableNode $table)
    {
        /** @var Form $fieldSet */
        $fieldSet = $this->createOroForm()->findField(ucfirst(Inflector::pluralize($fieldSetLabel)));
        $fieldSet->fill($table);
    }

    /**
     * Set collection field with set of values
     * Example: And set Reminders with:
     *            | Method        | Interval unit | Interval number |
     *            | Email         | days          | 1               |
     *            | Flash message | minutes       | 30              |
     *
     * @Given /^(?:|I )set (?P<field>[^"]+) with:$/
     */
    public function setCollectionFieldWith($field, TableNode $table)
    {
        $this->createOroForm()->fillField($field, $table);
    }

    /**
     * Check that the field contains the following values.
     *
     * Example: Then I should see values in field "Reminders":
     *            | Value 1 |
     *            | Value 2 |
     *
     * @Then /^(?:|I )should see values in field "(?P<field>(?:[^"]|\\")*)":$/
     */
    public function collectionFieldHasValues($field, TableNode $table)
    {
        $form = $this->createOroForm();
        $field = $form->findField($field);

        $value = $field->getValue();
        $expectedValues = $table->getColumn(0);
        foreach ($expectedValues as $expectedValue) {
            self::assertContains($expectedValue, $value);
        }
    }

    /**
     * Check that the field does not contain the following values.
     *
     * Example: Then I should not see values in field "Reminders":
     *            | Value 1 |
     *            | Value 2 |
     *
     * @Then /^(?:|I )should not see values in field "(?P<field>(?:[^"]|\\")*)":$/
     */
    public function collectionFieldHasNoValues($field, TableNode $table)
    {
        $form = $this->createOroForm();
        $field = $form->findField($field);

        $value = $field->getValue();
        $expectedValues = $table->getColumn(0);
        foreach ($expectedValues as $expectedValue) {
            self::assertNotContains($expectedValue, $value);
        }
    }

    /**
     * Add new embed form with data
     * Example: And add new address with:
     *            | Primary         | check               |
     *            | Country         | Ukraine             |
     *            | Street          | Myronosytska 57     |
     *            | City            | Kharkiv             |
     *            | Zip/Postal Code | 61000               |
     *            | State           | Kharkivs'ka Oblast' |
     *
     * @Given /^(?:|I )add new (?P<fieldSetLabel>[^"]+) with:$/
     */
    public function addNewFieldSetWith($fieldSetLabel, TableNode $table)
    {
        /** @var Form $fieldSet */
        $fieldSet = $this->createOroForm()->findField(ucfirst(Inflector::pluralize($fieldSetLabel)));
        $fieldSet->clickLink('Add');
        $this->waitForAjax();
        $form = $fieldSet->getLastSet();
        $form->fill($table);
    }

    /**
     * Assert form fields values
     * Example: And "User Form" must contains values:
     *            | Username          | charlie           |
     *            | First Name        | Charlie           |
     *            | Last Name         | Sheen             |
     *            | Primary Email     | charlie@sheen.com |
     *
     * @Then /^"(?P<formName>(?:[^"]|\\")*)" must contains values:$/
     */
    public function formMustContainsValues($formName, TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement($formName);
        $form->assertFields($table);
    }

    /**
     * Assert that field is required
     * Example: Then Opportunity Name is a required field
     * Example: Then Opportunity Name is a required field
     *
     * @Then /^(?P<label>[\w\s]+) is a required field$/
     */
    public function fieldIsRequired($label)
    {
        $labelElement = $this->getPage()->findElementContains('Label', $label);
        self::assertTrue($labelElement->hasClass('required'));
    }

    /**
     * Assert that field is not required
     * Example: Then Opportunity Name is not required field
     * Example: Then Opportunity Name is not required field
     *
     * @Then /^(?P<label>[\w\s]+) is not required field$/
     */
    public function fieldIsNotRequired($label)
    {
        $labelElement = $this->getPage()->findElementContains('Label', $label);
        self::assertFalse($labelElement->hasClass('required'));
    }

    /**
     * Type value in field chapter by chapter. Imitate real user input from keyboard
     * Example: And type "Common" in "search"
     * Example: When I type "Create" in "Enter shortcut action"
     *
     * @When /^(?:|I )type "(?P<value>(?:[^"]|\\")*)" in "(?P<field>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )type "(?P<value>(?:[^"]|\\")*)" in "(?P<field>(?:[^"]|\\")*)" from "(?P<formName>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException
     */
    public function iTypeInFieldWith($locator, $value, $formName = 'OroForm')
    {
        $locator = $this->fixStepArgument($locator);
        $value = $this->fixStepArgument($value);
        $formName = $this->fixStepArgument($formName);

        /** @var OroForm $form */
        $form = $this->createElement($formName);

        $form->typeInField($locator, $value);
    }

    /**
     * Example: And press select entity button on Owner field
     *
     * @Given press select entity button on :field field
     */
    public function pressSelectEntityButton($field)
    {
        $this->createOroForm()->pressEntitySelectEntityButton($field);
    }

    /**
     * This step is used for system configuration field
     * Go to System/Configuration and see the fields with default checkboxes
     * Example: And check "Use default" for "Position" field
     *
     * @Given check :checkbox for :label field
     */
    public function checkUseDefaultForField($label, $checkbox)
    {
        /** @var SystemConfigForm $form */
        $form = $this->createElement('SystemConfigForm');
        $form->checkCheckboxByLabel($label, $checkbox);
    }

    /**
     * This step used for system configuration field
     * Go to System/Configuration and see the fields with default checkboxes
     * Example: And uncheck "Use default" for "Position" field
     *
     * @Given uncheck :checkbox for :label field
     */
    public function uncheckUseDefaultForField($label, $checkbox)
    {
        /** @var SystemConfigForm $form */
        $form = $this->createElement('SystemConfigForm');
        $form->uncheckCheckboxByLabel($label, $checkbox);
    }

    /**
     * This step used for system configuration field
     * Go to System/Configuration and see the fields with default checkboxes
     * Example: And uncheck "Use default" for "Position" field in section "Section"
     *
     * @Given uncheck :checkbox for :label field in section :section
     */
    public function uncheckUseDefaultForFieldInSection($label, $checkbox, $section)
    {
        /** @var SystemConfigForm $form */
        $form = $this->createElement('SystemConfigForm');
        $form->uncheckCheckboxByLabel($label, $checkbox, $section);
    }

    /**
     * @Given /^(?:|I )uncheck "(?P<value>[^"]*)" element$/
     */
    public function iUncheckElement($elementName)
    {
        $element = $this->createElement($elementName);
        self::assertTrue($element->isIsset(), sprintf('Element "%s" not found', $elementName));

        $element->uncheck();
    }

    /**
     * @Given /^(?:|I )check "(?P<value>[^"]*)" element$/
     */
    public function iCheckElement($elementName)
    {
        $element = $this->createElement($elementName);
        self::assertTrue($element->isIsset(), sprintf('Element "%s" not found', $elementName));

        $element->check();
    }

    /**
     * @Then /^(?:|I )should see the following options for "(?P<label>[^"]*)" select:$/
     *
     * @param string $field
     * @param TableNode $options
     * @param string $formName
     */
    public function shouldSeeTheFollowingOptionsForSelect($field, TableNode $options, $formName = 'OroForm')
    {
        $optionLabels = array_merge(...$options->getRows());

        /** @var Select2Entity|Select $element */
        $element = $this->getFieldInForm($field, $formName);
        if ($element instanceof Select2Entity) {
            $options = $element->getSuggestedValues();
            foreach ($optionLabels as $optionLabel) {
                static::assertContains($optionLabel, $options);
            }
        } else {
            $this->assertSelectContainsOptions($field, $optionLabels);
        }
    }

    /**
     * @Then /^(?:|I )should not see the following options for "(?P<field>[^"]*)" select:$/
     *
     * @param string $field
     * @param TableNode $options
     * @param string $formName
     */
    public function shouldNotSeeTheFollowingOptionsForSelect($field, TableNode $options, $formName = 'OroForm')
    {
        $optionLabels = array_merge(...$options->getRows());

        /** @var Select2Entity|Select $element */
        $element = $this->getFieldInForm($field, $formName);
        if ($element instanceof Select2Entity) {
            $options = $element->getSuggestedValues();
            foreach ($optionLabels as $optionLabel) {
                static::assertNotContains($optionLabel, $options);
            }
        } else {
            $this->assertSelectNotContainsOptions($field, $optionLabels);
        }
    }

    /**
     * @Then /^I should see "([^"]*)" for "([^"]*)" select$/
     * @param string $label
     * @param string $field
     */
    public function iShouldSeeOptionForSelect($label, $field)
    {
        $this->assertSelectContainsOptions($field, [$label]);
    }

    /**
     * @Then /^I should see that option "([^"]*)" is selected in "([^"]*)" select$/
     * @param string $label
     * @param string $field
     */
    public function iShouldSeeThatOptionIsSelected($label, $field)
    {
        $selectedOptionText = $this->getSelectedOptionText($field);
        self::assertContains(
            $label,
            $selectedOptionText,
            sprintf(
                'Selected option with label "%s" doesn\'t contain text "%s" !',
                $selectedOptionText,
                $label
            )
        );
    }

    /**
     * @Then /^I should not see "([^"]*)" for "([^"]*)" select$/
     * @param string $label
     * @param string $field
     */
    public function iShouldNotSeeOptionForSelect($label, $field)
    {
        $this->assertSelectNotContainsOptions($field, [$label]);
    }

    /**
     * @param string $selectField
     * @param array $optionLabels
     */
    protected function assertSelectContainsOptions($selectField, array $optionLabels)
    {
        $selectOptionsText = $this->getSelectOptionsText($selectField);

        foreach ($optionLabels as $optionLabel) {
            static::assertContains($optionLabel, $selectOptionsText);
        }
    }

    /**
     * @param string $selectField
     * @param array $optionLabels
     */
    protected function assertSelectNotContainsOptions($selectField, array $optionLabels)
    {
        $selectOptionsText = $this->getSelectOptionsText($selectField);

        foreach ($optionLabels as $optionLabel) {
            static::assertNotContains($optionLabel, $selectOptionsText);
        }
    }

    /**
     * @param string $selectField
     *
     * @return array
     */
    protected function getSelectOptionsText($selectField)
    {
        /** @var Select $element */
        $element = $this->createElement($selectField);
        /** @var NodeElement[] $options */
        $options = $element->findAll('css', 'option');

        return array_map(function (NodeElement $option) {
            return $option->getText();
        }, $options);
    }

    /**
     * @param $selectField
     * @return string
     * @throws ElementNotFoundException
     */
    protected function getSelectedOptionText($selectField)
    {
        /** @var Select $element */
        $element = $this->createElement($selectField);
        /** @var NodeElement[] $options */
        $option = $element->getSelectedOption();

        if (null === $option) {
            $driver = $this->getSession()->getDriver();
            throw new ElementNotFoundException(
                $driver,
                'select option',
                'css',
                'option[selected]'
            );
        }

        return $option->getText();
    }

    /**.
     * @return OroForm
     */
    protected function createOroForm()
    {
        return $this->createElement('OroForm');
    }

    /**
     * @param string $fieldName
     * @param string $formName
     * @return NodeElement|mixed|null|Element
     * @throws ElementNotFoundException
     */
    protected function getFieldInForm($fieldName, $formName)
    {
        /** @var Form $form */
        $form = $this->createElement($formName);
        $mapping = $form->getOption('mapping');
        if ($mapping && isset($mapping[$fieldName])) {
            $field = $form->findField($mapping[$fieldName]);
            if (isset($mapping[$fieldName]['element'])) {
                $field = $this->elementFactory->wrapElement(
                    $mapping[$fieldName]['element'],
                    $field
                );
            }
        } else {
            $field = $form->findField($fieldName);
        }

        if (null === $field) {
            $driver = $this->getSession()->getDriver();
            throw new ElementNotFoundException(
                $driver,
                'form field',
                'id|name|label|value|placeholder|element',
                $fieldName
            );
        }

        return $field;
    }

    /**
     * This method introduces delay to wait for validation errors to appear.
     * It's useful when dealing with js validation errors as it can take some time for them to be rendered.
     *
     * @param callable $assertionFunction
     * @throws \Exception
     */
    private function waitForValidationErrorsAssertion(callable $assertionFunction)
    {
        /** @var \Exception $failureException */
        $failureException = null;

        $this->spin(function () use ($assertionFunction, &$failureException) {
            try {
                $assertionFunction();
            } catch (\Exception $exception) {
                $failureException = $exception;
                return null;
            }

            $failureException = null;
            return true;
        }, 5);

        if ($failureException) {
            throw $failureException;
        }
    }
}
