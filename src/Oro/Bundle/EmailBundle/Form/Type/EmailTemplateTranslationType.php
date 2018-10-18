<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TranslationBundle\Form\Type\GedmoTranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for email templates with multilocales option
 */
class EmailTemplateTranslationType extends AbstractType
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var string */
    protected $parentClass;

    /**
     * @param ConfigManager $configManager
     * @param string $parentClass
     */
    public function __construct(ConfigManager $configManager, string $parentClass)
    {
        $this->configManager = $configManager;
        $this->parentClass = $parentClass;
    }

    /**
     * Set labels for translation widget tabs
     *
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['labels'] = $options['labels'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $isWysiwygEnabled = $this->configManager->get('oro_form.wysiwyg_enabled');

        $resolver->setDefaults(
            [
                'translatable_class'   => 'Oro\\Bundle\\EmailBundle\\Entity\\EmailTemplate',
                'csrf_token_id'        => 'emailtemplate_translation',
                'labels'               => [],
                'content_options'      => [],
                'subject_options'      => [],
                'fields'               => function (Options $options) use ($isWysiwygEnabled) {
                    return [
                        'subject' => array_merge_recursive(
                            [
                                'field_type' => TextType::class
                            ],
                            $options['subject_options']
                        ),
                        'content' => array_merge_recursive(
                            [
                                'field_type'      => EmailTemplateRichTextType::class,
                                'attr'            => [
                                    'class'                => 'template-editor',
                                    'data-wysiwyg-enabled' => $isWysiwygEnabled,
                                ],
                                'wysiwyg_options' => [
                                    'height'     => '250px'
                                ]
                            ],
                            $options['content_options']
                        )
                    ];
                },
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parentClass;
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
        return 'oro_email_emailtemplate_translatation';
    }
}
