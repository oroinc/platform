<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Implements VirtualFieldProviderInterface for enum and multiEnum types
 */
class EnumVirtualFieldProvider implements VirtualFieldProviderInterface
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    protected $virtualFields = [];

    /**
     * Constructor
     *
     * @param ConfigProvider  $extendConfigProvider
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        ConfigProvider $extendConfigProvider,
        ManagerRegistry $doctrine
    ) {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->doctrine             = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFields($className)
    {
        $this->ensureVirtualFieldsInitialized($className);

        return isset($this->virtualFields[$className])
            ? array_keys($this->virtualFields[$className])
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualField($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized($className);

        return isset($this->virtualFields[$className][$fieldName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFieldQuery($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized($className);

        return $this->virtualFields[$className][$fieldName]['query'];
    }

    /**
     * Makes sure virtual fields for the given entity are loaded
     *
     * @param string $className
     */
    protected function ensureVirtualFieldsInitialized($className)
    {
        if (isset($this->virtualFields[$className])) {
            return;
        }

        $em = $this->getManagerForClass($className);
        if (!$em) {
            return;
        }

        $this->virtualFields[$className] = [];
        $metadata = $em->getClassMetadata($className);
        $associationNames = $metadata->getAssociationNames();
        foreach ($associationNames as $associationName) {
            if (!$this->extendConfigProvider->hasConfig($className, $associationName)) {
                continue;
            }
            $extendFieldConfig = $this->extendConfigProvider->getConfig($className, $associationName);
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $extendFieldConfig->getId();
            $fieldType     = $fieldConfigId->getFieldType();
            if ($fieldType === 'enum') {
                $this->virtualFields[$className][$associationName] = [
                    'query' => [
                        'select' => [
                            'expr'         => sprintf('target.%s', $extendFieldConfig->get('target_field')),
                            'return_type'  => $fieldType,
                            'filter_by_id' => true
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'  => sprintf('entity.%s', $associationName),
                                    'alias' => 'target'
                                ]
                            ]
                        ]
                    ]
                ];
            } elseif ($fieldType === 'multiEnum') {
                $this->virtualFields[$className][$associationName] = [
                    'query' => [
                        'select' => [
                            'expr'        => sprintf(
                                'entity.%s',
                                ExtendHelper::getMultiEnumSnapshotFieldName($associationName)
                            ),
                            'return_type'  => $fieldType,
                            'filter_by_id' => true
                        ]
                    ]
                ];
            }
        }
    }

    /**
     * Gets doctrine entity manager for the given class
     *
     * @param string $className
     *
     * @return EntityManager|null
     */
    protected function getManagerForClass($className)
    {
        try {
            return $this->doctrine->getManagerForClass($className);
        } catch (\ReflectionException $ex) {
            // ignore not found exception
        }

        return null;
    }
}
