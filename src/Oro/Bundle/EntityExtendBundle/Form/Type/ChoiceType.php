<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

/**
 * This form type is just a wrapper around standard 'choice' form type, but
 * this form type can handle 'require_schema_update' option that
 * allows to mark an entity as "Required Update" in case when a value of
 * an entity config attribute is changed.
 * An example of usage in entity_config.yml:
 * my_attr:
 *      options:
 *          require_schema_update: true
 *      form:
 *          type: oro_entity_extend_choice
 */
class ChoiceType extends AbstractConfigType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_entity_extend_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
