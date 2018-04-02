<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\MultipleEntityType as SymfonyMultipleEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class MultipleEntityType extends AbstractType
{
    const TYPE = 'oro_entity_extend_multiple_entity';

    /** @var RouterInterface */
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
            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($options) {
                    // add field to parent in order to be mapped correctly automatically
                    // because current field is filled by collection
                    $parentForm   = $event->getForm()->getParent();
                    $propertyName = $options['default_element'];

                    if (!$parentForm->has($propertyName)) {
                        $event->getForm()->getParent()->add(
                            $propertyName,
                            EntityIdentifierType::class,
                            [
                                'class'    => $options['class'],
                                'multiple' => false
                            ]
                        );
                    }
                }
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['extend' => false /* deprecated since 1.5, not used anymore */]);
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
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return SymfonyMultipleEntityType::class;
    }
}
