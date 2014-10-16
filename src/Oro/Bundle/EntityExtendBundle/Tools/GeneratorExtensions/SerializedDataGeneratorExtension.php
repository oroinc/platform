<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions;

use CG\Generator\PhpClass;
use CG\Generator\PhpProperty;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class SerializedDataGeneratorExtension extends AbstractEntityGeneratorExtension
{
    /** @var ConfigProvider */
    protected $extendConfigProvide;

    /**
     * @param ConfigProvider $extendConfigProvide
     */
    public function __construct(ConfigProvider $extendConfigProvide)
    {
        $this->extendConfigProvide = $extendConfigProvide;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array &$schema, PhpClass $class)
    {
        $class->setProperty(PhpProperty::create('serialized_data')->setVisibility('protected'));

        $schema['property']['serialized_data'] = 'serialized_data';
        $schema['doctrine'][$class->getName()]['fields']['serialized_data'] = [
            'column'   => 'serialized_data',
            'type'     => 'array',
            'nullable' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        /** @var ConfigInterface $config */
        $config = $this->extendConfigProvide->getConfig($schema['class']);

        return $config->get('has_serialized_data', false, false);
    }
}
