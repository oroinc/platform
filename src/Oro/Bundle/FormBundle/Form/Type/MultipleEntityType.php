<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\EventListener\MultipleEntitySubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The form type to select several entities from a list.
 */
class MultipleEntityType extends AbstractType
{
    private DoctrineHelper $doctrineHelper;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'added',
            EntityIdentifierType::class,
            [
                'class'    => $options['class'],
                'multiple' => true,
                'mapped'   => false,
            ]
        );
        $builder->add(
            'removed',
            EntityIdentifierType::class,
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
    public function configureOptions(OptionsResolver $resolver)
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
                'selection_url'              => null,
                'selection_url_method'       => null,
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
        $this->setOptionToView($view, $options, 'selection_url');
        $this->setOptionToView($view, $options, 'selection_url_method');
        $this->setOptionToView($view, $options, 'selection_route');
        $this->setOptionToView($view, $options, 'selection_route_parameters');
        $this->setOptionToView($view, $options, 'initial_elements');
        $this->setOptionToView($view, $options, 'selector_window_title');
        $this->setOptionToView($view, $options, 'default_element');
        $view->vars['allow_action'] =
            empty($options['add_acl_resource'])
            || $this->authorizationChecker->isGranted($options['add_acl_resource']);
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

    private function setOptionToView(FormView $view, array $options, string $option): void
    {
        $view->vars[$option] = $options[$option] ?? null;
    }
}
