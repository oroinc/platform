<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class EmailTemplateTranslationType extends AbstractType
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $isWysiwygEnabled = $this->configManager->get('oro_form.wysiwyg_enabled');

        $resolver->setDefaults(
            [
                'translatable_class'   => 'Oro\\Bundle\\EmailBundle\\Entity\\EmailTemplate',
                'intention'            => 'emailtemplate_translation',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'cascade_validation'   => true,
                'labels'               => [],
                'content_options'      => [],
                'subject_options'      => [],
                'fields'               => function (Options $options) use ($isWysiwygEnabled) {
                    return [
                        'subject' => array_merge(
                            [
                                'field_type' => 'text'
                            ],
                            $options->get('subject_options')
                        ),
                        'content' => array_merge(
                            [
                                'field_type'      => 'oro_email_template_rich_text',
                                'attr'            => [
                                    'class'                => 'template-editor',
                                    'data-wysiwyg-enabled' => $isWysiwygEnabled,
                                ],
                                'wysiwyg_options' => [
                                    'height'     => '250px'
                                ]
                            ],
                            $options->get('content_options')
                        )
                    ];
                },
            ]
        );
    }

    public function getParent()
    {
        return 'a2lix_translations_gedmo';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_email_emailtemplate_translatation';
    }
}
