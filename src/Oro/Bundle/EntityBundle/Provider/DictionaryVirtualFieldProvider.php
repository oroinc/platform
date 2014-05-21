<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class DictionaryVirtualFieldProvider implements VirtualFieldProviderInterface
{
    /** @var ConfigProvider */
    protected $groupingConfigProvider;

    /** @var ConfigProvider */
    protected $dictionaryConfigProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    protected $virtualFields = [];

    /** @var array */
    protected $dictionaries;

    /**
     * Constructor
     *
     * @param ConfigProvider  $groupingConfigProvider
     * @param ConfigProvider  $dictionaryConfigProvider
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        ConfigProvider $groupingConfigProvider,
        ConfigProvider $dictionaryConfigProvider,
        ManagerRegistry $doctrine
    ) {
        $this->groupingConfigProvider   = $groupingConfigProvider;
        $this->dictionaryConfigProvider = $dictionaryConfigProvider;
        $this->doctrine                 = $doctrine;
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

    protected function ensureVirtualFieldsInitialized($className)
    {
        if (!isset($this->virtualFields[$className])) {
            $this->ensureDictionariesInitialized();
            $this->virtualFields[$className] = [];

            $em               = $this->getManagerForClass($className);
            $metadata         = $em->getClassMetadata($className);
            $associationNames = $metadata->getAssociationNames();
            foreach ($associationNames as $associationName) {
                $targetClassName = $metadata->getAssociationTargetClass($associationName);
                if ($metadata->isSingleValuedAssociation($associationName)
                    && isset($this->dictionaries[$targetClassName])
                ) {
                    $defaultFieldName = $this->dictionaries[$targetClassName];
                    $targetMetadata   = $em->getClassMetadata($targetClassName);
                    $fieldName        = sprintf('%s_%s', $associationName, $defaultFieldName);

                    $this->virtualFields[$className][$fieldName] = [
                        'query' => [
                            'select' => [
                                'expr'        => sprintf('target.%s', $defaultFieldName),
                                'return_type' => $targetMetadata->getTypeOfField($defaultFieldName)
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
                }
            }
        }
    }

    /**
     * Makes sure the metadata for dictionary entities was loaded
     */
    protected function ensureDictionariesInitialized()
    {
        if (null === $this->dictionaries) {
            $this->dictionaries = [];
            $configs            = $this->groupingConfigProvider->getConfigs();
            foreach ($configs as $config) {
                $groups = $config->get('groups');
                if (!empty($groups) && in_array('dictionary', $groups)) {
                    $className = $config->getId()->getClassName();
                    if ($this->dictionaryConfigProvider->hasConfig($className)) {
                        $defaultFieldName = $this->dictionaryConfigProvider->getConfig($className);
                        if (!empty($defaultFieldName)) {
                            $this->dictionaries[$className] = $defaultFieldName;
                        }
                    }
                }
            }
        }
    }

    /**
     * Gets doctrine entity manager for the given class
     *
     * @param string $className
     * @return EntityManager
     * @throws InvalidEntityException
     */
    protected function getManagerForClass($className)
    {
        $manager = null;
        try {
            $manager = $this->doctrine->getManagerForClass($className);
        } catch (\ReflectionException $ex) {
            // ignore not found exception
        }
        if (!$manager) {
            throw new InvalidEntityException(sprintf('The "%s" entity was not found.', $className));
        }

        return $manager;
    }
}
