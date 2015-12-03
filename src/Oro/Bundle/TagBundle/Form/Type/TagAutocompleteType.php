<?php
namespace Oro\Bundle\TagBundle\Form\Type;

use Oro\Bundle\TagBundle\Entity\TagManager;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TagAutocompleteType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'configs' => array(
                    'placeholder'    => 'oro.tag.form.choose_or_create_tag',
                    'extra_config'   => 'multi_autocomplete',
                    'multiple'       => true
                ),
                'autocomplete_alias' => 'tags',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_tag_autocomplete';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }
}
