<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\FormBundle\Form\EventListener\MultipleEntitySubscriber;

class MultipleEntityType extends AbstractType
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param SecurityFacade $securityFacade
     */
    public function __construct(DoctrineHelper $doctrineHelper, SecurityFacade $securityFacade)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'added',
            'oro_entity_identifier',
            [
                'class'    => $options['class'],
                'multiple' => true,
                'mapped'   => false,
            ]
        );
        $builder->add(
            'removed',
            'oro_entity_identifier',
            [
                'class'    => $options['class'],
                'multiple' => true,
                'mapped'   => false,
            ]
        );

        $builder->addEventSubscriber(new MultipleEntitySubscriber($this->doctrineHelper));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['class']);
        $resolver->setDefaults(
            [
                'add_acl_resource'           => null,
                'class'                      => null,
                'default_element'            => null,
                'initial_elements'           => null,
                'selector_window_title'      => null,
                'extra_config'               => null,
                'grid_url'                   => null, // deprecated
                'selection_url'              => null,
                'selection_route'            => null,
                'selection_route_parameters' => [],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $this->setOptionToView($view, $options, 'extra_config');
        $this->setOptionToView($view, $options, 'grid_url'); // deprecated
        $this->setOptionToView($view, $options, 'selection_url');
        $this->setOptionToView($view, $options, 'selection_route');
        $this->setOptionToView($view, $options, 'selection_route_parameters');
        $this->setOptionToView($view, $options, 'initial_elements');
        $this->setOptionToView($view, $options, 'selector_window_title');
        $this->setOptionToView($view, $options, 'default_element');

        if (empty($options['add_acl_resource'])) {
            $options['allow_action'] = true;
        } else {
            $options['allow_action'] = $this->securityFacade->isGranted($options['add_acl_resource']);
        }

        $this->setOptionToView($view, $options, 'allow_action');
    }

    /**
     * @param FormView $view
     * @param array    $options
     * @param string   $option
     */
    protected function setOptionToView(FormView $view, array $options, $option)
    {
        $view->vars[$option] = isset($options[$option]) ? $options[$option] : null;
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
        return 'oro_multiple_entity';
    }
}
