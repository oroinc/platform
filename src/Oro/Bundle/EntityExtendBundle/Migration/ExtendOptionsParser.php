<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;

class ExtendOptionsParser
{
    /** @var EntityMetadataHelper */
    protected $entityMetadataHelper;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     * @param FieldTypeHelper      $fieldTypeHelper
     * @param ConfigManager        $configManager
     */
    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        FieldTypeHelper $fieldTypeHelper,
        ConfigManager $configManager
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->fieldTypeHelper      = $fieldTypeHelper;
        $this->configManager        = $configManager;
    }

    /**
     * Gets all options
     *
     * @param array $options
     * @return array
     */
    public function parseOptions(array $options)
    {
        $builder = new ExtendOptionsBuilder($this->entityMetadataHelper, $this->fieldTypeHelper, $this->configManager);

        $objectKeys = array_filter(
            array_keys($options),
            function ($key) {
                return strpos($key, '_') !== 0;
            }
        );

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

        // process auxiliary sections, such as append flags
        $auxiliarySections = array_filter(
            array_keys($options),
            function ($key) {
                return strpos($key, '_') === 0;
            }
        );
        foreach ($auxiliarySections as $sectionName) {
            $configType = $builder->getAuxiliaryConfigType($sectionName);
            $objectKeys = array_keys($options[$sectionName]);
            foreach ($objectKeys as $objectKey) {
                if (!strpos($objectKey, '!')) {
                    $builder->addTableAuxiliaryOptions(
                        $configType,
                        $objectKey,
                        $options[$sectionName][$objectKey]
                    );
                } else {
                    $keyParts = explode('!', $objectKey);
                    $builder->addColumnAuxiliaryOptions(
                        $configType,
                        $keyParts[0],
                        $keyParts[1],
                        $options[$sectionName][$objectKey]
                    );
                }
            }
        }

        return $builder->getOptions();
    }
}
