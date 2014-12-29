<?php
namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Tools\FieldAccessor;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class MultipleEntityType extends AbstractType
{
    /**
     * @var OroEntityManager
     */
    protected $entityManager;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    public function __construct($entityManager, SecurityFacade $securityFacade)
    {
        $this->entityManager  = $entityManager;
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
                    'multiple' => true
                ]
            )
            ->add(
                'removed',
                'oro_entity_identifier',
                [
                    'class'    => $options['class'],
                    'multiple' => true
                ]
            );

        if ($options['extend']) {
            $em    = $this->entityManager;
            $class = $options['class'];

            $builder->addEventListener(
                FormEvents::PRE_SUBMIT,
                function (FormEvent $event) use ($em, $class) {
                    $data       = $event->getData();
                    $repository = $em->getRepository($class);
                    $targetData = $event->getForm()->getParent()->getData();
                    $fieldName  = $event->getForm()->getName();

                    if (!empty($data['added'])) {
                        foreach (explode(',', $data['added']) as $id) {
                            $entity = $repository->find($id);
                            if ($entity) {
                                FieldAccessor::addValue($targetData, $fieldName, $entity);
                            }
                        }
                    }

                    if (!empty($data['removed'])) {
                        foreach (explode(',', $data['removed']) as $id) {
                            $entity = $repository->find($id);
                            if ($entity) {
                                FieldAccessor::removeValue($targetData, $fieldName, $entity);
                            }
                        }
                    }
                }
            );
        }
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
                'extend'                     => false,
                'initial_elements'           => null,
                'mapped'                     => false,
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
