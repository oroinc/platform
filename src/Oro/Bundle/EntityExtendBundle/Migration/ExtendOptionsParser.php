<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;

class ExtendOptionsParser
{
    /** @var EntityMetadataHelper */
    protected $entityMetadataHelper;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     * @param FieldTypeHelper      $fieldTypeHelper
     */
    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        FieldTypeHelper $fieldTypeHelper
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->fieldTypeHelper      = $fieldTypeHelper;
    }

    /**
     * Gets all options
     *
     * @param array $options
     * @return array
     */
    public function parseOptions(array $options)
    {
        $builder = new ExtendOptionsBuilder($this->entityMetadataHelper, $this->fieldTypeHelper);

        $objectKeys = array_keys($options);

        // at first all table's options should be processed,
        // because it is possible that a reference to new table is created
        foreach ($objectKeys as $objectKey) {
            if (!strpos($objectKey, '!')) {
                $builder->addTableOptions($objectKey, $options[$objectKey]);
            }
        }

        // next column's options for all tables can be processed
        foreach ($objectKeys as $objectKey) {
            if (strpos($objectKey, '!')) {
                $keyParts = explode('!', $objectKey);
                $builder->addColumnOptions($keyParts[0], $keyParts[1], $options[$objectKey]);
            }
        }

        return $builder->getOptions();
    }
}
