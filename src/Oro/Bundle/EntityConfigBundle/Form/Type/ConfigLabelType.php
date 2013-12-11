<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Form\DataTransformer\ConfigLabelTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\Translator;

class ConfigLabelType extends AbstractType
{
    const NAME = 'oro_entity_config_label_type';

    /** @var Translator */
    protected $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        parent::buildForm($builder, $options);

//        $builder->add(
//            'is_default',
//            'checkbox',
//            [
//                'label' => 'Use default',
//                'required' => false,
//                'block' => 'entity',
//                'subblock' => 'general'
//            ]
//        );

//        $builder->add('blabla', 'text');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
//        $resolver->setDefaults(
//            [
//                'auto_initialized' => false
//            ]
//        );
    }

    /**
     * {@inheritdoc}
     */
//    public function getParent()
//    {
//        return 'oro_entity_config_type';
//    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
