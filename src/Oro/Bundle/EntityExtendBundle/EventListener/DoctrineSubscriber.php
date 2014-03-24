<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

class DoctrineSubscriber implements EventSubscriber
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
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata'
        );
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

                $this->prepareRelations($config, $cmBuilder);
            }

            $em->getMetadataFactory()->setMetadataFor($className, $event->getClassMetadata());
        }
    }

    /**
     * @param ConfigInterface $config
     * @param ClassMetadataBuilder $cmBuilder
     */
    protected function prepareRelations(ConfigInterface $config, ClassMetadataBuilder $cmBuilder)
    {
        if ($config->is('relation')) {
            foreach ($config->get('relation') as $relation) {
                /** @var FieldConfigId $fieldId */
                if ($relation['assign'] && $fieldId = $relation['field_id']) {
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
}
