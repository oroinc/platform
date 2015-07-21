<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AutoResponseTemplateType extends AbstractType
{
    /** @var ConfigManager */
    private $userConfig;

    /** @var LocaleSettings */
    private $localeSettings;

    /**
     * @param ConfigManager $userConfig
     * @param LocaleSettings    $localeSettings
     */
    public function __construct(ConfigManager $userConfig, LocaleSettings $localeSettings)
    {
        $this->userConfig     = $userConfig;
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', 'choice', [
                'label'    => 'oro.email.emailtemplate.type.label',
                'multiple' => false,
                'expanded' => true,
                'choices'  => [
                    'html' => 'oro.email.datagrid.emailtemplate.filter.type.html',
                    'txt'  => 'oro.email.datagrid.emailtemplate.filter.type.txt'
                ],
                'data'     => 'html',
                'required' => true
            ])
            ->add('translations', 'oro_email_emailtemplate_translatation', [
                'label'    => 'oro.email.emailtemplate.translations.label',
                'locales'  => $this->getLanguages(),
                'labels'   => $this->getLocaleLabels(),
            ])
            ->add('visible', 'checkbox', [
                'label' => 'oro.email.autoresponserule.form.template.visible.label',
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            if ($form->has('owner')) {
                $form->remove('owner');
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\EmailBundle\Entity\EmailTemplate',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_email_autoresponse_template';
    }

    /**
     * @return array
     */
    protected function getLanguages()
    {
        $languages = $this->userConfig->get('oro_locale.languages');

        return array_unique(array_merge($languages, [$this->localeSettings->getLanguage()]));
    }

    /**
     * @return array
     */
    protected function getLocaleLabels()
    {
        return $this->localeSettings->getLocalesByCodes($this->getLanguages(), $this->localeSettings->getLanguage());
    }
}
