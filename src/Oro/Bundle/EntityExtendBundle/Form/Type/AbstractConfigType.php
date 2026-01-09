<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Form\Type\AbstractConfigType as BaseAbstractConfigType;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;
use Oro\Bundle\EntityExtendBundle\Form\EventListener\ConfigTypeSubscriber;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides common functionality for form types that work with entity config attributes affecting extend entity schemas.
 *
 * This base class extends the entity config form type to add schema update tracking. When config attributes
 * that impact the database schema are changed, it marks the entity as requiring a schema update.
 * The 'schema_update_required' option controls when entities should be flagged for schema regeneration.
 */
abstract class AbstractConfigType extends BaseAbstractConfigType
{
    protected ConfigManager $configManager;

    public function __construct(ConfigTypeHelper $typeHelper, ConfigManager $configManager)
    {
        parent::__construct($typeHelper);
        $this->configManager = $configManager;
    }

    #[\Override]
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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'schema_update_required' => function ($newVal, $oldVal) {
                // we cannot use strict comparison here because Symfony form
                // converts empty value (false, 0, empty string) => null, true => 1
                return $newVal != $oldVal;
            },
        ]);
    }
}
