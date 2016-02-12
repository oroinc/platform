<?php

namespace Oro\Bundle\TagBundle\Converter;

use InvalidArgumentException;

use Oro\Bundle\ImportExportBundle\Converter\RelationCalculatorInterface;

class TaggableRelationCalculator implements RelationCalculatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getMaxRelatedEntities($entityName, $fieldName)
    {
        if ($fieldName !== 'tags') {
            throw new InvalidArgumentException('Field must be "tags" for taggable relation calculator');
        }

        return 0;
    }
}
