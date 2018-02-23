<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressCollectionType extends AbstractType
{
    const NAME = 'oro_address_collection';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizers(
            array(
                'options' => function (Options $options, $values) {
                    if (!$values) {
                        $values = array();
                    }
                    $values['single_form'] = false;

                    return $values;
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_collection';
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
        return self::NAME;
    }
}
