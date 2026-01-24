<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;

/**
 * Calculates the maximum number of related entities based on template fixtures.
 *
 * This implementation examines template fixture data to determine the maximum count
 * of related entities for a given field. It iterates through fixture instances and
 * inspects the field values to find the largest collection, which is then used to
 * determine the number of columns needed for export format representation.
 */
class TemplateFixtureRelationCalculator implements RelationCalculatorInterface
{
    /** @var TemplateManager */
    protected $templateManager;

    /** @var FieldHelper */
    protected $fieldHelper;

    public function __construct(TemplateManager $templateManager, FieldHelper $fieldHelper)
    {
        $this->templateManager = $templateManager;
        $this->fieldHelper     = $fieldHelper;
    }

    #[\Override]
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
