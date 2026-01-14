<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\DataMapper\EmailTemplateDataMapperFactory;
use Oro\Bundle\EmailBundle\Form\DataMapper\LocalizationAwareEmailTemplateDataMapper;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type which can be used for create/edit EmailTemplate entity.
 */
class EmailTemplateType extends AbstractType
{
    private ?EmailTemplateDataMapperFactory $emailTemplateDataMapperFactory = null;

    public function __construct(
        private ConfigManager $userConfig,
        private LocalizationManager $localizationManager
    ) {
    }

    public function setEmailTemplateDataMapperFactory(
        ?EmailTemplateDataMapperFactory $emailTemplateDataMapperFactory
    ): void {
        $this->emailTemplateDataMapperFactory = $emailTemplateDataMapperFactory;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $localizations = $this->localizationManager->getLocalizations();

        $builder
            ->add('name', TextType::class, [
                'label' => 'oro.email.emailtemplate.name.label',
                'required' => true,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'oro.email.emailtemplate.type.label',
                'multiple' => false,
                'expanded' => true,
                'choices' => [
                    'oro.email.datagrid.emailtemplate.filter.type.html' => 'html',
                    'oro.email.datagrid.emailtemplate.filter.type.txt' => 'txt',
                ],
                'required' => true,
            ])
            ->add('entityName', EmailTemplateEntityChoiceType::class, [
                'label' => 'oro.email.emailtemplate.entity_name.label',
                'tooltip' => 'oro.email.emailtemplate.entity_name.tooltip',
                'required' => false,
                'configs' => ['allowClear' => true],
            ])
            ->add('translations', EmailTemplateTranslationCollectionType::class, [
                'localizations' => $localizations,
                'wysiwyg_enabled' => $this->userConfig->get('oro_email.email_template_wysiwyg_enabled') ?? false,
                'wysiwyg_options' => $this->getWysiwygOptions(),
            ])
            ->add('activeLocalization', HiddenType::class, [
                'mapped' => false,
                'attr' => ['class' => 'active-localization'],
            ])
            ->add('parentTemplate', HiddenType::class, [
                'label' => 'oro.email.emailtemplate.parent.label',
                'property_path' => 'parent',
            ]);

        $builder->get('activeLocalization')->addModelTransformer(new CallbackTransformer(
            function ($data) {
                return null;
            },
            function ($data) use ($localizations) {
                return $localizations[(int)$data] ?? null;
            }
        ));

        // disable some fields for non editable email template
        $setDisabled = function (&$options) {
            if (isset($options['auto_initialize'])) {
                $options['auto_initialize'] = false;
            }
            $options['disabled'] = true;
        };
        $factory = $builder->getFormFactory();
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($factory, $setDisabled) {
                $data = $event->getData();
                if ($data && $data->getId() && $data->getIsSystem()) {
                    $form = $event->getForm();
                    // entityName field
                    $options = $form->get('entityName')->getConfig()->getOptions();
                    $setDisabled($options);
                    $form->add($factory->createNamed(
                        'entityName',
                        EmailTemplateEntityChoiceType::class,
                        null,
                        $options
                    ));
                    // name field
                    $options = $form->get('name')->getConfig()->getOptions();
                    $setDisabled($options);
                    $form->add($factory->createNamed('name', TextType::class, null, $options));
                    if (!$data->getIsEditable()) {
                        // name field
                        $options = $form->get('type')->getConfig()->getOptions();
                        $setDisabled($options);
                        $form->add($factory->createNamed('type', ChoiceType::class, null, $options));
                    }
                }
            }
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->onPreSetDataTranslationsField(...));
        $builder->get('entityName')->addEventListener(
            FormEvents::POST_SUBMIT,
            $this->onPostSubmitTranslationsField(...)
        );

        // BC layer.
        if (!$this->emailTemplateDataMapperFactory) {
            $builder->setDataMapper(new LocalizationAwareEmailTemplateDataMapper($builder->getDataMapper()));
        } else {
            $builder->setDataMapper($this->emailTemplateDataMapperFactory->createDataMapper($builder->getDataMapper()));
        }
    }

    private function onPreSetDataTranslationsField(FormEvent $event): void
    {
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $event->getData();
        $entityClass = $emailTemplate?->getEntityName();
        if ($entityClass === null) {
            return;
        }

        FormUtils::replaceField($event->getForm(), 'translations', [
            'entity_class' => $entityClass
        ]);
    }

    private function onPostSubmitTranslationsField(FormEvent $event): void
    {
        $data = $event->getData();

        if (!empty($data)) {
            FormUtils::replaceField($event->getForm()->getParent(), 'translations', [
                'entity_class' => $data,
            ]);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\EmailBundle\Entity\EmailTemplate',
                'csrf_token_id' => 'emailtemplate',
                'error_mapping' => [
                    'attachments' => 'translations.default.attachments',
                ],
            ]
        );
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_email_emailtemplate';
    }

    /**
     * @return array
     */
    protected function getWysiwygOptions()
    {
        $options = [
            'convert_urls' => false
        ];

        if ($this->userConfig->get('oro_email.sanitize_html')) {
            return $options;
        }

        return array_merge($options, [
            'valid_elements' => null, //all elements are valid
            'plugins' => array_merge(OroRichTextType::$defaultPlugins, ['fullscreen']),
            'relative_urls' => false,
        ]);
    }
}
