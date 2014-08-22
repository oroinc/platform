<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrganizationType extends AbstractType
{
    /** @var SecurityContext */
    protected $securityContext;

    public function __construct(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'enabled',
                'choice',
                [
                    'required' => true,
                    'label'    => 'oro.organization.enabled.label',
                    'choices'  => [1 => 'Active', 0 => 'Inactive']
                ]
            )
            ->add(
                'name',
                'text',
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
                'textarea',
                [
                    'required' => false,
                    'label'    => 'oro.organization.description.label'
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $data = $form->getData();
        if ($data) {
            /** @var UsernamePasswordOrganizationToken $token */
            $token = $this->securityContext->getToken();
            $currentOrganization = $token->getOrganizationContext();
            if ($data->getId() == $currentOrganization->getId()) {
                $view->children['enabled']->vars['required'] = false;
                $view->children['enabled']->vars['disabled'] = true;
                $view->children['enabled']->vars['value']    = true;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'         => 'Oro\Bundle\OrganizationBundle\Entity\Organization',
                'intention'          => 'organization',
                'cascade_validation' => true,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_organization';
    }
}
