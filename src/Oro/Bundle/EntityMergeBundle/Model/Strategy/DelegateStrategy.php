<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class DelegateStrategy implements StrategyInterface
{
    /**
     * @var StrategyInterface[]
     */
    protected $elements;

    /**
     * @param array $fieldMergers
     */
    public function __construct(array $fieldMergers = array())
    {
        $this->elements = array();

        foreach ($fieldMergers as $fieldMerger) {
            $this->add($fieldMerger);
        }
    }

    /**
     * @param StrategyInterface $fieldMerger
     */
    public function add(StrategyInterface $fieldMerger)
    {
        $this->elements[] = $fieldMerger;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(FieldData $fieldData)
    {
        $delegate = $this->match($fieldData);

        if (!$delegate) {
            throw new InvalidArgumentException(
                sprintf('Cannot find merge strategy for "%s" field.', $fieldData->getFieldName())
            );
        }

        $delegate->merge($fieldData);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FieldData $fieldData)
    {
        return $this->match($fieldData) !== null;
    }

    /**
     * Match field data and field merger
     *
     * @param FieldData $fieldData
     * @return StrategyInterface|null
     */
    protected function match(FieldData $fieldData)
    {
        foreach ($this->elements as $fieldMerger) {
            if ($fieldMerger->supports($fieldData)) {
                return $fieldMerger;
            }
        }
        return null;
    }
}
