<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Form\EventListener\ConfigTypeSubscriber;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;
use Oro\Bundle\EntityConfigBundle\Form\Type\AbstractConfigType as BaseAbstractConfigType;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * The abstract class for form types are used to work with entity config attributes
 * which can impact a schema of extend entities.
 * Supported options:
 *  require_schema_update - if set to true an entity will be marked as "Required Update" in case
 *                          when a value of an entity config attribute is changed
 */
abstract class AbstractConfigType extends BaseAbstractConfigType
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigTypeHelper $typeHelper
     * @param ConfigManager    $configManager
     */
    public function __construct(ConfigTypeHelper $typeHelper, ConfigManager $configManager)
    {
        parent::__construct($typeHelper);
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /*
         * Prevents to register twice exactly the same listener (which causes issues)
         *
         * This is due to strange hierarchies in form types where
         * Oro\Bundle\ActivityBundle\Form\Type\MultipleAssociationChoiceType has this class in hierarchy
         * + has defined Oro\Bundle\EntityExtendBundle\Form\Type\MultipleAssociationChoiceType as parent type,
         * which also has this class in it's hierarchy
         * (instead of just extending symfony's abstract type and using form extensions/inheritance
         * to avoid such issues)
         */
        if (ArrayUtil::some(
            function ($listener) {
                return is_array($listener) && $listener[0] instanceof ConfigTypeSubscriber;
            },
            $builder->getEventDispatcher()->getListeners(FormEvents::POST_SUBMIT)
        )) {
                return;
        }

        $builder->addEventSubscriber(
            new ConfigTypeSubscriber($this->configManager, $options['schema_update_required'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults([
            'schema_update_required' => function ($newVal, $oldVal) {
                // we cannot use strict comparison here because Symfony form
                // converts empty value (false, 0, empty string) => null, true => 1
                return $newVal != $oldVal;
            },
        ]);
    }
}
