<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Form\EventListener\AdditionalEmailsSubscriber;
use Oro\Bundle\NotificationBundle\Form\EventListener\ContactInformationEmailsSubscriber;

class EmailNotificationType extends AbstractType
{
    const NAME = 'emailnotification';

    /** @var BuildTemplateFormSubscriber */
    protected $buildTemplateSubscriber;

    /** @var AdditionalEmailsSubscriber */
    protected $additionalEmailsSubscriber;

    /** @var RouterInterface */
    private $router;

    /** @var ContactInformationEmailsSubscriber */
    protected $contactInformationEmailsSubscriber;

    /**
     * @param BuildTemplateFormSubscriber $buildTemplateSubscriber
     * @param AdditionalEmailsSubscriber $additionalEmailsSubscriber
     * @param RouterInterface $router
     * @param ContactInformationEmailsSubscriber $contactInformationEmailsSubscriber
     */
    public function __construct(
        BuildTemplateFormSubscriber $buildTemplateSubscriber,
        AdditionalEmailsSubscriber $additionalEmailsSubscriber,
        RouterInterface $router,
        ContactInformationEmailsSubscriber $contactInformationEmailsSubscriber
    ) {
        $this->buildTemplateSubscriber = $buildTemplateSubscriber;
        $this->additionalEmailsSubscriber = $additionalEmailsSubscriber;
        $this->router = $router;
        $this->contactInformationEmailsSubscriber = $contactInformationEmailsSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->buildTemplateSubscriber);
        $builder->addEventSubscriber($this->additionalEmailsSubscriber);
        $builder->addEventSubscriber($this->contactInformationEmailsSubscriber);

        $builder->add(
            'entityName',
            EmailNotificationEntityChoiceType::NAME,
            [
                'label'       => 'oro.notification.emailnotification.entity_name.label',
                'tooltip'     => 'oro.notification.emailnotification.entity_name.tooltip',
                'required'    => true,
                'configs'     => [
                    'allowClear' => true
                ],
                'tooltip_parameters' => [
                    'url' => $this->router->generate('oro_email_emailtemplate_index')
                ],
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ]
        );

        $builder->add(
            'event',
            'oro_select2_entity',
            [
                'label'         => 'oro.notification.emailnotification.event.label',
                'class'         => 'OroNotificationBundle:Event',
                'property'      => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')->orderBy('c.name', 'ASC');
                },
                'configs'       => [
                    'allowClear'  => true,
                    'placeholder' => 'oro.notification.form.choose_event',
                ],
                'attr' => [
                    'autocomplete' => 'off'
                ],
                'empty_value'   => '',
                'required'      => true
            ]
        );

        $builder->add(
            'template',
            'oro_select2_translatable_entity',
            [
                'label' => 'oro.notification.emailnotification.template.label',
                'class' => 'OroEmailBundle:EmailTemplate',
                'configs' => [
                    'allowClear' => true,
                    'placeholder' => 'oro.email.form.choose_template',
                ],
                'empty_value' => '',
                'required' => true
            ]
        );

        $builder->add(
            'recipientList',
            RecipientListType::NAME,
            [
                'label'    => 'oro.notification.emailnotification.recipient_list.label',
                'required' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => EmailNotification::class,
                'intention' => self::NAME
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['listenChangeElements'] = array_filter(
            array_map(
                function (FormView $view) {
                    if ($view->vars['name'] === 'entityName') {
                        return '#' . $view->vars['id'];
                    }

                    return null;
                },
                array_values($view->children)
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
        return self::NAME;
    }
}
