<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for EmailTemplateTranslation entity
 */
class EmailTemplateTranslationType extends AbstractType
{
    /** @var string Check content on wysiwyg empty formatting */
    private const EMPTY_REGEX = '#^(\r*\n*)*'
        . '\<!DOCTYPE html\>(\r*\n*)*'
        . '\<html\>(\r*\n*)*'
        . '\<head\>(\r*\n*)*\</head\>(\r*\n*)*'
        . '\<body\>(\r*\n*)*\</body\>(\r*\n*)*'
        . '\</html\>(\r*\n*)*$#';

    /** @var TranslatorInterface */
    private $translator;

    /** @var LocalizationManager */
    private $localizationManager;

    public function __construct(TranslatorInterface $translator, LocalizationManager $localizationManager)
    {
        $this->translator = $translator;
        $this->localizationManager = $localizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'attr' => [
                    'maxlength' => 255,
                ],
                'required' => false,
            ])
            ->add('content', EmailTemplateRichTextType::class, [
                'attr' => [
                    'class' => 'template-editor',
                    'data-wysiwyg-enabled' => $options['wysiwyg_enabled'],
                ],
                'required' => false,
                'wysiwyg_options' => $options['wysiwyg_options'],
            ]);

        if ($options['localization']) {
            $fallbackLabel = $this->getFallbackLabel($options['localization']);

            $builder
                ->add('subjectFallback', CheckboxType::class, [
                    'label' => $fallbackLabel,
                    'required' => false,
                    'block_name' => 'fallback_checkbox',
                ])
                ->add('contentFallback', CheckboxType::class, [
                    'label' => $fallbackLabel,
                    'required' => false,
                    'block_name' => 'fallback_checkbox',
                ]);
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            $notNullRequired = true;
            if ($form->has('subjectFallback')) {
                $notNullRequired = empty($data['subjectFallback']);
            }

            if ($notNullRequired) {
                FormUtils::replaceField($form, 'subject', ['constraints' => [new NotBlank()]]);
            }
        });
        $builder->addViewTransformer($this->getViewTransformer($options));
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['localization_id'] = null;
        $view->vars['localization_title'] = null;
        $view->vars['localization_parent_id'] = null;

        if (isset($options['localization'])) {
            /** @var Localization $localization */
            $localization = $options['localization'];
            $view->vars['localization_id'] = $localization->getId();
            $view->vars['localization_title'] = $localization->getTitle(
                $this->localizationManager->getDefaultLocalization()
            );

            if ($localization->getParentLocalization()) {
                $view->vars['localization_parent_id'] = $localization->getParentLocalization()->getId();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailTemplateTranslation::class,
            'allow_extra_fields' => true,
            'localization' => null,
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
        ]);

        $resolver->setRequired('allow_extra_fields');
        $resolver->setAllowedTypes('localization', ['null', Localization::class]);
        $resolver->setAllowedTypes('wysiwyg_enabled', ['bool']);
        $resolver->setAllowedTypes('wysiwyg_options', ['array']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_email_emailtemplate_localization';
    }

    private function getFallbackLabel(Localization $localization): string
    {
        if ($localization->getParentLocalization()) {
            $fallbackLabel = $this->translator->trans(
                'oro.email.emailtemplatetranslation.form.use_parent_localization',
                [
                    '%name%' => $localization->getParentLocalization()->getTitle(
                        $this->localizationManager->getDefaultLocalization()
                    ),
                ]
            );
        } else {
            $fallbackLabel = $this->translator->trans(
                'oro.email.emailtemplatetranslation.form.use_default_localization'
            );
        }

        return $fallbackLabel;
    }

    private function getViewTransformer(array $options): CallbackTransformer
    {
        return new CallbackTransformer(
            static function ($data) use ($options) {
                // Create localized template for localization
                if (!$data) {
                    $data = new EmailTemplateTranslation();
                    $data->setLocalization($options['localization']);
                }

                return $data;
            },
            static function ($data) {
                // Clear empty input
                if ($data instanceof EmailTemplateTranslation) {
                    $subject = $data->getSubject();
                    if ($subject === null || trim($subject) === '') {
                        $data->setSubject(null);
                    }

                    if (preg_match(self::EMPTY_REGEX, trim($data->getContent()))) {
                        $data->setContent(null);
                    }
                }

                return $data;
            }
        );
    }
}
