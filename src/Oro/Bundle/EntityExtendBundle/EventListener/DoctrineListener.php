<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

class DoctrineListener
{
    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     *
     * TODO: remove ' = null' in the next release. It is related to https://magecore.atlassian.net/browse/BAP-3543
     */
    public function __construct(ExtendDbIdentifierNameGenerator $nameGenerator = null)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        /** @var OroEntityManager $em */
        $em = $event->getEntityManager();

        $configProvider = $em->getExtendConfigProvider();
        $className      = $event->getClassMetadata()->getName();

        if (!ConfigHelper::isConfigModelEntity($className) && $configProvider->hasConfig($className)) {
            $config = $configProvider->getConfig($className);
            if ($config->is('is_extend')) {
                $cmBuilder = new ClassMetadataBuilder($event->getClassMetadata());

                if ($config->is('index')) {
                    foreach ($config->get('index') as $columnName => $enabled) {
                        $fieldConfig = $configProvider->getConfig($className, $columnName);

                        if ($enabled && !$fieldConfig->is('state', ExtendScope::STATE_NEW)) {
                            $indexName = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
                                $className,
                                $columnName
                            );
                            $cmBuilder->addIndex([$columnName], $indexName);
                        }
                    }
                }

                $this->prepareRelations($em, $config, $cmBuilder);
            }

            $em->getMetadataFactory()->setMetadataFor($className, $event->getClassMetadata());
        }
    }

    /**
     *
     * @param OroEntityManager     $em
     * @param ConfigInterface      $config
     * @param ClassMetadataBuilder $cmBuilder
     */
    protected function prepareRelations(
        OroEntityManager $em,
        ConfigInterface $config,
        ClassMetadataBuilder $cmBuilder
    ) {
        if ($config->is('relation')) {
            $relations = $config->get('relation');
            foreach ($relations as $relation) {
                /** @var FieldConfigId|Config $fieldId */
                $fieldId = $relation['field_id'];
                if ($relation['assign'] && $fieldId) {
                    /** @var FieldConfigId $targetFieldId */
                    $targetFieldId = $relation['target_field_id'];

                    $targetFieldName = $targetFieldId
                        ? $targetFieldId->getFieldName()
                        : null;

                    $fieldName   = $fieldId->getFieldName();
                    $defaultName = ExtendConfigDumper::DEFAULT_PREFIX . $fieldId->getFieldName();

                    switch ($fieldId->getFieldType()) {
                        case 'manyToOne':
                            $builder = $cmBuilder->createManyToOne($fieldName, $relation['target_entity']);
                            if ($targetFieldName) {
                                $builder->inversedBy($targetFieldName);
                            }
                            $builder->addJoinColumn(
                                $fieldName . '_id',
                                'id',
                                true,
                                false,
                                'SET NULL'
                            );

                            if ($this->isAttachment($em, $config->getId()->getClassName(), $fieldName)) {
                                $builder->cascadePersist();
                            }

                            $builder->cascadeDetach();
                            $builder->build();
                            break;
                        case 'oneToMany':
                            /** create 1:* */
                            $builder = $cmBuilder->createOneToMany($fieldName, $relation['target_entity']);
                            $builder->mappedBy($targetFieldName);

                            $builder->cascadeDetach();
                            $builder->build();

                            /** create 1:1 default */
                            $builder = $cmBuilder->createOneToOne($defaultName, $relation['target_entity']);
                            $builder->addJoinColumn($defaultName . '_id', 'id', true, false, 'SET NULL');
                            $builder->build();
                            break;
                        case 'manyToMany':
                            if ($relation['owner']) {
                                $builder = $cmBuilder->createManyToMany($fieldName, $relation['target_entity']);
                                if ($targetFieldName) {
                                    $builder->inversedBy($targetFieldName);
                                }

                                $builder->setJoinTable(
                                    $this->nameGenerator->generateManyToManyJoinTableName(
                                        $fieldId->getClassName(),
                                        $fieldId->getFieldName(),
                                        $relation['target_entity']
                                    )
                                );
                                $builder->build();

                                $builder = $cmBuilder->createOneToOne($defaultName, $relation['target_entity']);
                                $builder->addJoinColumn($defaultName . '_id', 'id', true, false, 'SET NULL');

                                $builder->build();
                            } else {
                                $cmBuilder->addInverseManyToMany(
                                    $fieldName,
                                    $relation['target_entity'],
                                    $targetFieldName
                                );
                            }
                            break;
                    }
                }
            }
        }
    }

    /**
     * @param OroEntityManager $em
     * @param string           $className
     * @param string           $fieldName
     *
     * @return bool
     */
    protected function isAttachment($em, $className, $fieldName)
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $em->getExtendConfigProvider()->getConfigManager()->getProvider('attachment')->getId(
            $className,
            $fieldName
        );

        return in_array($fieldConfigId->getFieldType(), AttachmentScope::$attachmentTypes);
    }
}
