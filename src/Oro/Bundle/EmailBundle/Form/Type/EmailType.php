<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

class EmailType extends AbstractType
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gridName', 'hidden', ['required' => false])
            ->add('entityClass', 'hidden', ['required' => false])
            ->add('entityId', 'hidden', ['required' => false])
            ->add(
                'from',
                'oro_email_email_address',
                ['required' => true, 'label' => 'oro.email.from_email_address.label']
            )
            ->add('to', 'oro_email_email_address', ['required' => true, 'multiple' => true])
            ->add('subject', 'text', ['required' => true, 'label' => 'oro.email.subject.label'])
            ->add('body', 'textarea', ['required' => false, 'label' => 'oro.email.email_body.label'])
            ->add(
                'template',
                'oro_email_template_list',
                [
                    'label' => 'oro.email.template.label',
                    'required' => false,
                    'depends_on_parent_field' => 'entityClass',
                    'configs' => [
                        'allowClear' => true
                    ]
                ]
            )
            ->add(
                'type',
                'choice',
                [
                    'label'      => 'oro.email.type.label',
                    'required'   => true,
                    'data'       => 'txt',
                    'choices'  => [
                        'html' => 'oro.email.datagrid.emailtemplate.filter.type.html',
                        'txt'  => 'oro.email.datagrid.emailtemplate.filter.type.txt'
                    ],
                    'expanded'   => true
                ]
            )
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'initChoicesByEntityName']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'initChoicesByEntityName']);

    }

    /**
     * @param FormEvent $event
     */
    public function initChoicesByEntityName(FormEvent $event)
    {
        /** @var Email|array $data */
        $data = $event->getData();
        if (null === $data ||
            is_array($data) && empty($data['entityClass']) ||
            is_object($data) && null === $data->getEntityClass()) {
            return;
        }

        $entityClass = is_object($data) ? $data->getEntityClass() : $data['entityClass'];
        $form = $event->getForm();

        /** @var UsernamePasswordOrganizationToken $token */
        $token        = $this->securityContext->getToken();
        $organization = $token->getOrganizationContext();

        FormUtils::replaceField(
            $form,
            'template',
            [
                'selectedEntity' => $entityClass,
                'query_builder'  =>
                    function (EmailTemplateRepository $templateRepository) use (
                        $entityClass,
                        $organization
                    ) {
                        return $templateRepository->getEntityTemplatesQueryBuilder($entityClass, $organization, true);
                    },
            ],
            ['choice_list', 'choices']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'Oro\Bundle\EmailBundle\Form\Model\Email',
                'intention'          => 'email',
                'csrf_protection'    => true,
                'cascade_validation' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_email_email';
    }
}
