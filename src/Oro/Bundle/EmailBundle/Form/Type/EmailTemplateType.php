<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\DataMapper\LocalizationAwareEmailTemplateDataMapper;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
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
    /** @var ConfigManager */
    private $userConfig;

    /** @var LocalizationManager */
    private $localizationManager;

    public function __construct(ConfigManager $userConfig, LocalizationManager $localizationManager)
    {
        $this->userConfig = $userConfig;
        $this->localizationManager = $localizationManager;
    }

    /**
     * {@inheritdoc}
     */
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
                'wysiwyg_enabled' => $this->userConfig->get('oro_form.wysiwyg_enabled') ?? false,
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

        $builder->setDataMapper(new LocalizationAwareEmailTemplateDataMapper($builder->getDataMapper()));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\EmailBundle\Entity\EmailTemplate',
                'csrf_token_id' => 'emailtemplate',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
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
            'entity_encoding' => 'raw',
        ]);
    }
}
