<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;
use CG\Generator\PhpProperty;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class SerializedDataGeneratorExtension extends AbstractEntityGeneratorExtension
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(ConfigProvider $extendConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array &$schema, PhpClass $class)
    {
        $entityClassName = $class->getName();

        /**
         * Entity processing
         */
        $class->setProperty(PhpProperty::create('serialized_data')->setVisibility('protected'));
        $schema['property']['serialized_data'] = 'serialized_data';
        $schema['doctrine'][$entityClassName]['fields']['serialized_data'] = [
            'column'   => 'serialized_data',
            'type'     => 'array',
            'nullable' => true,
        ];

        /**
         * Entity fields processing
         */
        /** @var FieldConfigId[] $config */
        $fieldConfigs = $this->extendConfigProvider->getConfigs($entityClassName);
        foreach ($fieldConfigs as $fieldConfig) {
            if ($fieldConfig->get('is_serialized')) {
                $fieldName = $fieldConfig->getId()->getFieldName();
                unset($schema['doctrine'][$entityClassName]['fields'][$fieldName]);
                unset($schema['property'][$fieldName]);

                if ($class->hasMethod('get' . ucfirst(Inflector::camelize($fieldName)))) {
                    $class->removeMethod('get' . ucfirst(Inflector::camelize($fieldName)));
                }
                if ($class->hasMethod('set' . ucfirst(Inflector::camelize($fieldName)))) {
                    $class->removeMethod('set' . ucfirst(Inflector::camelize($fieldName)));
                }

                $class
                    ->setMethod(
                        $this->generateClassMethod(
                            'get' . ucfirst(Inflector::camelize($fieldName)),
                            'return $this->serialized_data[\'' . $fieldName . '\'];'
                        )
                    )
                    ->setMethod(
                        $this->generateClassMethod(
                            'set' . ucfirst(Inflector::camelize($fieldName)),
                            '$this->serialized_data[\'' . $fieldName . '\'] = $value; return $this;',
                            ['value']
                        )
                    );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        /** @var ConfigInterface $config */
        $config = $this->extendConfigProvider->getConfig($schema['class']);

        return
            $config->get('has_serialized_data', false, false)
            || $config->get('owner') == ExtendScope::OWNER_CUSTOM;
    }
}
