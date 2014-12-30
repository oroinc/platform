<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MultipleEntityType extends AbstractType
{
    const TYPE = 'oro_entity_extend_multiple_entity';

    /** @var RouterInterface */
    protected $router;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param RouterInterface $router
     * @param ManagerRegistry $registry
     */
    public function __construct(RouterInterface $router, ManagerRegistry $registry)
    {
        $this->router   = $router;
        $this->registry = $registry;
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

    /**
     * {@inheritdoc}
     */
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['extend' => false /* deprecated since 1.5, not used anymore */]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_multiple_entity';
    }
}
