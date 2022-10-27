<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * A filter that can be used to filter data by an association with composite identifier.
 * This filter supports only "equal" and "not equal" comparisons.
 * Also filtering by several identifiers is supported.
 */
class AssociationCompositeIdentifierFilter extends CompositeIdentifierFilter implements FieldAwareFilterInterface
{
    private ?string $field = null;

    /**
     * {@inheritDoc}
     */
    public function setField(string $field): void
    {
        $this->field = $field;
    }

    /**
     * {@inheritDoc}
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFieldPath(string $fieldName): string
    {
        return sprintf('%s.%s', $this->getField(), parent::getFieldPath($fieldName));
    }
}
