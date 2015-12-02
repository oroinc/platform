<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

abstract class RemoveDuplicates implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array  $sectionConfig
     * @param string $entityClass
     *
     * @return array
     */
    protected function removeDuplicates(array $sectionConfig, $entityClass)
    {
        if (empty($sectionConfig[ConfigUtil::FIELDS])) {
            return $sectionConfig;
        }

        $keys = array_keys($sectionConfig[ConfigUtil::FIELDS]);
        foreach ($keys as $key) {
            $fieldPath = !empty($sectionConfig[ConfigUtil::FIELDS][$key][ConfigUtil::PROPERTY_PATH])
                ? $sectionConfig[ConfigUtil::FIELDS][$key][ConfigUtil::PROPERTY_PATH]
                : $key;
            $path      = ConfigUtil::explodePropertyPath($fieldPath);
            if (count($path) === 1) {
                continue;
            }
            $fieldName = array_pop($path);
            if (array_key_exists(implode(ConfigUtil::PATH_DELIMITER, $path), $sectionConfig[ConfigUtil::FIELDS])) {
                $metadata = $this->doctrineHelper->findEntityMetadataByPath($entityClass, $path);
                if (null !== $metadata && in_array($fieldName, $metadata->getIdentifierFieldNames(), true)) {
                    unset($sectionConfig[ConfigUtil::FIELDS][$key]);
                }
            }
        }

        return $sectionConfig;
    }
}
