<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrganizationType extends AbstractType
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'enabled',
                ChoiceType::class,
                [
                    'required' => true,
                    'label'    => 'oro.organization.enabled.label',
                    'choices'  => ['Active' => 1, 'Inactive' => 0]
                ]
            )
            ->add(
                'name',
                TextType::class,
                [
                    'required'    => true,
                    'label'       => 'oro.organization.name.label',
                    'constraints' => [
                        new NotBlank()
                    ]
                ]
            )
            ->add(
                'description',
                OroResizeableRichTextType::class,
                [
                    'required' => false,
                    'label'    => 'oro.organization.description.label'
                ]
            );
        // we should set enabled for current organization because form change enabled property to false
        // if 'enabled' field is disabled
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                if (is_object($data) && $data->getId() === $this->tokenAccessor->getOrganizationId()) {
                    $data->setEnabled(true);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $data = $form->getData();
        if ($data) {
            if ($data->getId() == $this->tokenAccessor->getOrganizationId()) {
                $view->children['enabled']->vars['required'] = false;
                $view->children['enabled']->vars['disabled'] = true;
                $view->children['enabled']->vars['value']    = true;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'         => 'Oro\Bundle\OrganizationBundle\Entity\Organization',
                'csrf_token_id'      => 'organization',
            )
        );
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
        return 'oro_organization';
    }
}
