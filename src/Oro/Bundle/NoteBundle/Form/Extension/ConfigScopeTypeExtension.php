<?php

namespace Oro\Bundle\NoteBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigScopeTypeExtension extends AbstractTypeExtension
{
    const SCOPE        = 'note';
    const ATTR_ENABLED = 'enabled';

    /**
     * @var ConfigProvider
     */
    protected $noteConfigProvider;

    public function __construct(
        ConfigProvider $noteConfigProvider
    ) {
        $this->noteConfigProvider = $noteConfigProvider;
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $propertyName = $builder->getName();
        if ($propertyName == self::ATTR_ENABLED
            && isset($options['config_id'])
            && $options['config_id'] instanceof EntityConfigId
            && $options['config_id']->getScope() == self::SCOPE
        ) {
            $entityConfig = $this->noteConfigProvider->getConfigById($options['config_id']);
            $formItems    = $this->noteConfigProvider->getPropertyConfig()->getFormItems();

            /**
             * Disable field on editAction if it enabled
             */
            if ($entityConfig->get(self::ATTR_ENABLED) == true) {
                $options['disabled'] = true;
                $this->appendClassAttr($options, 'disabled-' . $formItems[self::ATTR_ENABLED]['form']['type']);

                $builder->setDisabled(true);
                $builder->setAttribute('options', $options);

//                $builder->remove(self::ATTR_ENABLED);
//                $builder->add(
//                    self::ATTR_ENABLED,
//                    $formItems[self::ATTR_ENABLED]['form']['type'],
//                    $options
//                );

            }

            //parent::buildForm($builder, $options);
            //$builder->remove(self::ATTR_ENABLED);
            //$a = 1;

            /*
            if (isset($config['options']['enable_only'])
                //&& $this->configModel->getId()
                && $config->get(self::ATTR_ENABLED) == true
            ) {
                $options['disabled'] = true;
                //$this->appendClassAttr($options, 'disabled-' . $config['form']['type']);
            }
            */
        }
        //$builder->get
    }

    protected function appendClassAttr(array &$options, $cssClass)
    {
        if (isset($options['attr']['class'])) {
            $options['attr']['class'] .= ' ' . $cssClass;
        } else {
            $options['attr']['class'] = $cssClass;
        }
    }

    /**
     * @inheritdoc
     */
    public function getExtendedType()
    {
        return 'oro_entity_config_scope_type';
    }
}
