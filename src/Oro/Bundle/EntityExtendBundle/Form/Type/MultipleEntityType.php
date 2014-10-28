<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class MultipleEntityType extends AbstractType
{
    const TYPE = 'oro_entity_extend_multiple_entity';

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['default_element'])) {
            $builder->add(
                $options['default_element'],
                'oro_entity_identifier',
                [
                    'class'    => $options['class'],
                    'multiple' => false
                ]
            );
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $data = $view->parent->vars['value'];
        if (is_object($data)) {
            $view->vars['grid_url'] = $this->router->generate(
                'oro_entity_relation',
                [
                    'id'         => $data->getId() ? $data->getId() : 0,
                    'entityName' => str_replace(
                        '\\',
                        '_',
                        get_class($data)
                    ),
                    'fieldName'  => $form->getName()
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::TYPE;
    }

    public function getParent()
    {
        return 'oro_multiple_entity';
    }
}
