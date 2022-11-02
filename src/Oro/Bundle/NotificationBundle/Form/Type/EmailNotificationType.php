<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Form\EventListener\AdditionalEmailsSubscriber;
use Oro\Bundle\NotificationBundle\Form\EventListener\ContactInformationEmailsSubscriber;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

/**
 * Form type for EmailNotification entity
 */
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

    /** @var array */
    private $events;

    public function __construct(
        BuildTemplateFormSubscriber $buildTemplateSubscriber,
        AdditionalEmailsSubscriber $additionalEmailsSubscriber,
        RouterInterface $router,
        ContactInformationEmailsSubscriber $contactInformationEmailsSubscriber,
        array $events
    ) {
        $this->buildTemplateSubscriber = $buildTemplateSubscriber;
        $this->additionalEmailsSubscriber = $additionalEmailsSubscriber;
        $this->router = $router;
        $this->contactInformationEmailsSubscriber = $contactInformationEmailsSubscriber;
        $this->events = $events;
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
            EmailNotificationEntityChoiceType::class,
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
            'eventName',
            Select2ChoiceType::class,
            [
                'label'         => 'oro.notification.emailnotification.event_name.label',
                'choices'     => array_combine($this->events, $this->events),
                'configs'       => [
                    'allowClear'  => true,
                    'placeholder' => 'oro.notification.form.choose_event',
                ],
                'attr' => [
                    'autocomplete' => 'off'
                ],
                'placeholder'   => '',
                'required'      => true
            ]
        );

        $builder->add(
            'template',
            Select2TranslatableEntityType::class,
            [
                'label' => 'oro.notification.emailnotification.template.label',
                'class' => 'OroEmailBundle:EmailTemplate',
                'choice_label' => 'name',
                'configs' => [
                    'allowClear' => true,
                    'placeholder' => 'oro.email.form.choose_template',
                ],
                'placeholder' => '',
                'required' => true
            ]
        );

        $builder->add(
            'recipientList',
            RecipientListType::class,
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
                'csrf_token_id' => self::NAME
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
