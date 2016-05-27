<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\AbstractExclusionProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore
 * "snapshot" field of multi-enum type.
 */
class EnumExclusionProvider extends AbstractExclusionProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var int */
    private $snapshotSuffixOffset;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager        = $configManager;
        $this->snapshotSuffixOffset = -strlen(ExtendHelper::ENUM_SNAPSHOT_SUFFIX);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        // check for "snapshot" field of multi-enum type
        if (substr($fieldName, $this->snapshotSuffixOffset) === ExtendHelper::ENUM_SNAPSHOT_SUFFIX) {
            $guessedName = substr($fieldName, 0, $this->snapshotSuffixOffset);
            if (!empty($guessedName) && $this->isMultiEnumField($metadata->name, $guessedName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isMultiEnumField($className, $fieldName)
    {
        if ($this->configManager->hasConfig($className, $fieldName)) {
            /** @var FieldConfigId $fieldId */
            $fieldId = $this->configManager->getId('extend', $className, $fieldName);
            if ($fieldId->getFieldType() === 'multiEnum') {
                return true;
            }
        }

        return false;
    }
}
