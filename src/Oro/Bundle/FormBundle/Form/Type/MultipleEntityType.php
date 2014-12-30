<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class MultipleEntityType extends AbstractType
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'added',
                'oro_entity_identifier',
                [
                    'class'    => $options['class'],
                    'multiple' => true,
                    'mapped'   => false,
                ]
            )
            ->add(
                'removed',
                'oro_entity_identifier',
                [
                    'class'    => $options['class'],
                    'multiple' => true,
                    'mapped'   => false,
                ]
            );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();

                $added   = $form->get('added')->getData();
                $removed = $form->get('removed')->getData();

                /** @var Collection $collection */
                $collection = $form->getData();
                foreach ($added as $relation) {
                    $collection->add($relation);
                }

                foreach ($removed as $relation) {
                    $collection->removeElement($relation);
                }
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                /** @var PersistentCollection $collection */
                $collection = $form->getData();
                if ($collection instanceof PersistentCollection && $collection->isDirty()) {
                    $added   = $collection->getInsertDiff();
                    $removed = $collection->getDeleteDiff();

                    $form->get('added')->setData($added);
                    $form->get('removed')->setData($removed);
                }
            }
        );
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
        return 'oro_multiple_entity';
    }
}
