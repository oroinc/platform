<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;

class ConfigExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        // config_is_new is true if a new entity or field is created
        // note that this option is false if entity or field is edited even if its state is New
        // (extend.state is ExtendScope::STATE_NEW)
        $resolver->setOptional(['config_id', 'config_is_new']);
        $resolver->setAllowedTypes(
            [
                'config_id' => [
                    'Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId',
                    'Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId'
                ]
            ]
        );
    }
}
