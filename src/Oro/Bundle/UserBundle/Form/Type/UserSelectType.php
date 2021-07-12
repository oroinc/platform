<?php
namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for User select.
 */
class UserSelectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'users',
                'configs'            => [
                    'placeholder'             => 'oro.user.form.choose_user',
                    'result_template_twig'    => '@OroUser/User/Autocomplete/result.html.twig',
                    'selection_template_twig' => '@OroUser/User/Autocomplete/selection.html.twig'
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroEntitySelectOrCreateInlineType::class;
    }

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
        return 'oro_user_select';
    }
}
