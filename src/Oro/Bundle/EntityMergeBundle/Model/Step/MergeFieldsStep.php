<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;

class MergeFieldsStep implements DependentMergeStepInterface
{
    /**
     * @param StrategyInterface $strategy
     */
    public function __construct(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * Merge fields
     *
     * @param EntityData $data
     */
    public function run(EntityData $data)
    {
        foreach ($data->getFields() as $field) {
            $this->strategy->merge($field);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDependentSteps()
    {
        return array('Oro\\Bundle\\EntityMergeBundle\\Model\\Step\\ValidateStep');
    }
}
