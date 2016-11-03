<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;

class TemplateFixtureRelationCalculator implements RelationCalculatorInterface
{
    /** @var TemplateManager */
    protected $templateManager;

    /** @var FieldHelper */
    protected $fieldHelper;

    /**
     * @param TemplateManager $templateManager
     * @param FieldHelper     $fieldHelper
     */
    public function __construct(TemplateManager $templateManager, FieldHelper $fieldHelper)
    {
        $this->templateManager = $templateManager;
        $this->fieldHelper     = $fieldHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxRelatedEntities($entityName, $fieldName)
    {
        $maxFields = 1;
        $fixtures = $this->templateManager->getEntityFixture($entityName)->getData();
        foreach ($fixtures as $fixture) {
            try {
                $fieldValue = $this->fieldHelper->getObjectValue($fixture, $fieldName);
                if ($fieldValue instanceof \Countable || is_array($fieldValue)) {
                    $itemsCount = count($fieldValue);
                    if ($itemsCount > $maxFields) {
                        $maxFields = $itemsCount;
                    }
                }
            } catch (\Exception $e) {
                // there is no $fieldName in fixture
                continue;
            }
        }

        return $maxFields;
    }
}
