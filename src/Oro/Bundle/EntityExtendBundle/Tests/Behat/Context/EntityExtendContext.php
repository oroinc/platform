<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\FormBundle\Tests\Behat\Element\Select2Entity;
use Oro\Bundle\ImportExportBundle\Tests\Behat\Context\ImportExportContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class EntityExtendContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary;
    use KernelDictionary;

    /**
     * @var ImportExportContext
     */
    private $importExportContext;

    /**
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->importExportContext = $environment->getContext(ImportExportContext::class);
    }

    /**
     * @Then /^"([^"]*)" was set to "([^"]*)" and is not editable$/
     */
    public function wasSetToAndIsNotEditable($field, $isBidirectional)
    {
        /** @var Select2Entity $element */
        $element = $this->createElement($field);
        $select2Div = $element->getParent();

        static::assertEquals($isBidirectional, $this->createElement('EntitySelector', $select2Div)->getText());
        static::assertNotNull($this->createElement('Select2Entity', $select2Div)->getAttribute('disabled'));
    }

    /**
     * @Then /^I should not see "([^"]*)" entity for "([^"]*)" select$/
     */
    public function iShouldNotSeeEntityForSelect($entityName, $field)
    {
        /** @var Select2Entity $element */
        $element = $this->createElement($field);
        $element->fillSearchField($entityName);

        $suggestions = $element->getSuggestions();
        static::assertCount(1, $suggestions);
        static::assertEquals('No matches found', reset($suggestions)->getText());

        $element->close();
    }

    /**
     * Download data template for extend entity
     *
     * @When /^(?:|I )download "(?P<entity>([\w\s]+))" extend entity Data Template file$/
     * @param string $entityAlias
     */
    public function iDownloadDataTemplateFileForExtendEntity($entityAlias)
    {
        $className = $this->getContainer()->get('oro_entity.entity_alias_resolver')->getClassByAlias($entityAlias);
        $entityConfigManager = $this->getContainer()->get('oro_entity_config.config_manager');
        $entityModel = $entityConfigManager->getConfigEntityModel($className);

        static::assertNotNull($entityModel, sprintf('No entity model found for class "%s"', $className));

        $this->importExportContext->downloadTemplateFileByProcessor(
            'oro_entity_config_entity_field.export_template',
            ['entity_id' => $entityModel->getId()]
        );
    }
}
