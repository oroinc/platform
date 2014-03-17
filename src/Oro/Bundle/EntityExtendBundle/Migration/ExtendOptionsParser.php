<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

class ExtendOptionsParser
{
    /**
     * @var EntityMetadataHelper
     */
    protected $entityMetadataHelper;

    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     */
    public function __construct(EntityMetadataHelper $entityMetadataHelper)
    {
        $this->entityMetadataHelper = $entityMetadataHelper;
    }

    /**
     * Gets all options
     *
     * @param array $options
     * @return array
     */
    public function parseOptions(array $options)
    {
        $builder = new ExtendOptionsBuilder($this->entityMetadataHelper);

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
