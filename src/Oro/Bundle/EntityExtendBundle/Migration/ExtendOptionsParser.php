<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;

/**
 * Extend options parser
 */
class ExtendOptionsParser
{
    /** @var EntityMetadataHelper */
    protected $entityMetadataHelper;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var bool */
    protected $isDryRunMode = false;

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
     * @param bool $isDryRunMode
     */
    public function setDryRunMode($isDryRunMode = false)
    {
        $this->isDryRunMode = $isDryRunMode;
    }

    /**
     * Gets all options
     *
     * @param array $options
     * @return array
     */
    public function parseOptions(array $options)
    {
        $builder = new ExtendOptionsBuilder(
            $this->entityMetadataHelper,
            $this->fieldTypeHelper,
            $this->configManager,
            $this->isDryRunMode
        );

        $objectKeys = array_filter(
            array_keys($options),
            function ($key) {
                return !str_starts_with($key, '_');
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
                return str_starts_with($key, '_');
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
