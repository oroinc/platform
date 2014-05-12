<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ConfigBundle\Config\UserConfigManager;

class EmailTemplateType extends AbstractType
{
    /**
     * @var UserConfigManager
     */
    private $userConfig;

    /**
     * @var LocaleSettings
     */
    private $localeSettings;

    /**
     * @param UserConfigManager $userConfig
     * @param LocaleSettings    $localeSettings
     */
    public function __construct(UserConfigManager $userConfig, LocaleSettings $localeSettings)
    {
        $this->userConfig     = $userConfig;
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'entityName',
            'oro_entity_choice',
            array(
                'label'    => 'oro.email.emailtemplate.entity_name.label',
                'required' => true
            )
        );

        $builder->add(
            'name',
            'text',
            array(
                'label'    => 'oro.email.emailtemplate.name.label',
                'required' => true
            )
        );

        $builder->add(
            'type',
            'choice',
            array(
                'label'    => 'oro.email.emailtemplate.type.label',
                'multiple' => false,
                'expanded' => true,
                'choices'  => array(
                    'html' => 'oro.email.datagrid.emailtemplate.filter.type.html',
                    'txt'  => 'oro.email.datagrid.emailtemplate.filter.type.txt'
                ),
                'required' => true
            )
        );

        $lang              = $this->localeSettings->getLanguage();
        $notificationLangs = $this->userConfig->get('oro_locale.languages');
        $notificationLangs = array_merge($notificationLangs, [$lang]);
        $localeLabels      = $this->localeSettings->getLocalesByCodes($notificationLangs, $lang);
        $builder->add(
            'translations',
            'oro_email_emailtemplate_translatation',
            array(
                'label'    => 'oro.email.emailtemplate.translations.label',
                'required' => false,
                'locales'  => $notificationLangs,
                'labels'   => $localeLabels,
            )
        );

        $builder->add(
            'parentTemplate',
            'hidden',
            array(
                'label'         => 'oro.email.emailtemplate.parent.label',
                'property_path' => 'parent'
            )
        );

        // disable some fields for non editable email template
        $setDisabled = function (&$options) {
            if (isset($options['auto_initialize'])) {
                $options['auto_initialize'] = false;
            }
            $options['disabled'] = true;
        };
        $factory     = $builder->getFormFactory();
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($factory, $setDisabled) {
                $data = $event->getData();
                if ($data && $data->getId() && $data->getIsSystem()) {
                    $form = $event->getForm();
                    // entityName field
                    $options = $form->get('entityName')->getConfig()->getOptions();
                    $setDisabled($options);
                    $form->add($factory->createNamed('entityName', 'oro_entity_choice', null, $options));
                    // name field
                    $options = $form->get('name')->getConfig()->getOptions();
                    $setDisabled($options);
                    $form->add($factory->createNamed('name', 'text', null, $options));
                    if (!$data->getIsEditable()) {
                        // name field
                        $options = $form->get('type')->getConfig()->getOptions();
                        $setDisabled($options);
                        $form->add($factory->createNamed('type', 'choice', null, $options));
                    }
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'           => 'Oro\Bundle\EmailBundle\Entity\EmailTemplate',
                'intention'            => 'emailtemplate',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'cascade_validation'   => true,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_email_emailtemplate';
    }
}
