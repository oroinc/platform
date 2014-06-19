<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureRegistry;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

class TemplateFixtureRelationCalculator implements RelationCalculatorInterface
{
    /**
     * @var TemplateFixtureRegistry
     */
    protected $fixtureRegistry;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var \Iterator
     */
    protected $fixtureData;

    /**
     * @param TemplateFixtureRegistry $fixtureRegistry
     * @param FieldHelper $fieldHelper
     */
    public function __construct(TemplateFixtureRegistry $fixtureRegistry, FieldHelper $fieldHelper)
    {
        $this->fixtureRegistry = $fixtureRegistry;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxRelatedEntities($entityName, $fieldName)
    {
        $maxFields = 0;
        foreach ($this->getFixtureData($entityName) as $fixture) {
            $fieldValue = $this->fieldHelper->getObjectValue($fixture, $fieldName);
            if ($fieldValue instanceof \Countable || is_array($fieldValue)) {
                $itemsCount = count($fieldValue);
                if ($itemsCount > $maxFields) {
                    $maxFields = $itemsCount;
                }
            }
        }

        return $maxFields;
    }

    /**
     * @param string $entityName
     * @return \Iterator
     * @throws LogicException
     */
    protected function getFixtureData($entityName)
    {
        if (!$this->fixtureData) {
            if (!$this->fixtureRegistry->hasEntityFixture($entityName)) {
                throw new LogicException(
                    sprintf('There is no template fixture registered for "%s".', $entityName)
                );
            }

            $this->fixtureData = $this->fixtureRegistry->getEntityFixture($entityName)->getData();
        }

        return $this->fixtureData;
    }
}
