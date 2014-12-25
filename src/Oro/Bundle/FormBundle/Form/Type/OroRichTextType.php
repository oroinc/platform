<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Templating\Asset\PackageInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\DataTransformer\StripTagsTransformer;

class OroRichTextType extends AbstractType
{
    const NAME = 'oro_rich_text';

    /**
     * @var PackageInterface
     */
    protected $assetHelper;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param PackageInterface $assetHelper
     */
    public function setAssetHelper(PackageInterface $assetHelper)
    {
        $this->assetHelper = $assetHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $allowableTags = null;
        if (!empty($options['wysiwyg_options']['valid_elements'])) {
            $allowableTags = $options['wysiwyg_options']['valid_elements'];
        }

        $transformer = new StripTagsTransformer($allowableTags);
        $builder->addModelTransformer($transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $toolbar = ['undo redo | bold italic underline | forecolor backcolor | bullist numlist | code | link'];
        $elements = [
            'a[href|target=_blank]',
            'ul',
            'ol',
            'li',
            'em[style]',
            'strong',
            'b',
            'p',
            'font[color]',
            'i',
            'br[data-mce-bogus]',
            'span[style|data-mce-style]'
        ];
        $defaultWysiwygOptions = [
            'plugins' => ['textcolor', 'code', 'link'],
            'toolbar' => $toolbar,
            'skin_url' => 'bundles/oroform/css/tinymce',
            'valid_elements' => implode(',', $elements),
            'menubar' => false,
            'statusbar' => false
        ];

        $defaults = [
            'wysiwyg_enabled' => (bool)$this->configManager->get('oro_form.wysiwyg_enabled'),
            'wysiwyg_options' => $defaultWysiwygOptions,
            'page-component' => [
                'module' => 'oroui/js/app/components/view-component',
                'options' => [
                    'view' => 'oroform/js/app/views/wysiwig-editor/wysiwyg-editor-view',
                    'content_css' => 'bundles/oroform/css/wysiwyg-editor.css',
                ]
            ],
        ];

        $resolver->setDefaults($defaults);
        $resolver->setNormalizers(
            [
                'wysiwyg_options' => function (Options $options, $wysiwygOptions) use ($defaultWysiwygOptions) {
                    return array_merge($defaultWysiwygOptions, $wysiwygOptions);
                },
                'attr' => function (Options $options, $attr) {
                    $pageComponent = $options->get('page-component');
                    $wysiwygOptions = (array)$options->get('wysiwyg_options');

                    if ($this->assetHelper) {
                        if (!empty($pageComponent['options']['content_css'])) {
                            $pageComponent['options']['content_css'] = $this->assetHelper
                                ->getUrl($pageComponent['options']['content_css']);
                        }
                        if (!empty($wysiwygOptions['skin_url'])) {
                            $wysiwygOptions['skin_url'] = $this->assetHelper->getUrl($wysiwygOptions['skin_url']);
                        }
                    }
                    $pageComponent['options'] = array_merge($pageComponent['options'], $wysiwygOptions);
                    $pageComponent['options']['enabled'] = (bool)$options->get('wysiwyg_enabled');

                    $attr['data-page-component-module'] = $pageComponent['module'];
                    $attr['data-page-component-options'] = json_encode($pageComponent['options']);

                    return $attr;
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'textarea';
    }
}
