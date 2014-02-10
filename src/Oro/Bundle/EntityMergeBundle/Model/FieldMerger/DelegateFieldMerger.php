<?php

namespace Oro\Bundle\EntityMergeBundle\Model\FieldMerger;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class DelegateFieldMerger implements FieldMergerInterface
{
    /**
     * @var FieldMergerInterface[]
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
     * @param FieldMergerInterface $fieldMerger
     */
    public function add(FieldMergerInterface $fieldMerger)
    {
        $this->elements[] = $fieldMerger;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(FieldData $fieldData)
    {
        $matched = $this->match($fieldData);

        if (!$matched) {
            throw new InvalidArgumentException(sprintf('Field "%s" cannot be merged.', $fieldData->getFieldName()));
        }

        $matched->merge($fieldData);
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
     * @return FieldMergerInterface|null
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
