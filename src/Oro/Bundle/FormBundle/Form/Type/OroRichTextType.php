<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides WYSIWYG editor functionality. WYSIWYG editor can be disabled from System Configuration.
 * @see https://www.tiny.cloud/docs/configure/editor-appearance/
 */
class OroRichTextType extends AbstractType
{
    const NAME            = 'oro_rich_text';
    const TOOLBAR_DEFAULT = 'default';
    const TOOLBAR_SMALL   = 'small';
    const TOOLBAR_LARGE   = 'large';

    /** @var AssetHelper */
    protected $assetHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var HtmlTagProvider */
    protected $htmlTagProvider;

    /** @var ContextInterface */
    protected $context;

    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    /**
     * @url http://www.tinymce.com/wiki.php/Configuration:toolbar
     * @var array
     */
    public static $toolbars = [
        self::TOOLBAR_SMALL  => ['undo redo | bold italic underline | bullist numlist | link image | fullscreen'],
        self::TOOLBAR_DEFAULT => [
            'undo redo | formatselect | bold italic underline | forecolor backcolor | bullist numlist ' .
            '| alignleft aligncenter alignright alignjustify | link image | fullscreen'
        ],
        self::TOOLBAR_LARGE => [
            'undo redo | formatselect | bold italic underline | forecolor backcolor | bullist numlist' .
            '| alignleft aligncenter alignright alignjustify | link image | fullscreen'
        ],
    ];

    /**
     * @var array
     */
    public static $defaultPlugins = [
        'code', 'link', 'fullscreen', 'lists', 'image', 'advlist'
    ];

    public function __construct(
        ConfigManager $configManager,
        HtmlTagProvider $htmlTagProvider,
        ContextInterface $context,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->configManager   = $configManager;
        $this->htmlTagProvider = $htmlTagProvider;
        $this->context         = $context;
        $this->htmlTagHelper   = $htmlTagHelper;
    }

    public function setAssetHelper(AssetHelper $assetHelper)
    {
        $this->assetHelper = $assetHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (null !== $options['wysiwyg_options']['valid_elements']) {
            $builder->addModelTransformer(new SanitizeHTMLTransformer($this->htmlTagHelper));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $assetsBaseUrl = ltrim($this->context->getBasePath() . '/', '/');
        $assetsVersionBaseUrl   = '';
        $assetsVersionFormatted = '';
        if ($this->assetHelper) {
            /**
             * As we can't get "assets_version_format" parameter and method "getVersion" returns only version value
             * without any formatting - we have to calculate formatted version url's parameter to be used inside
             * WYSIWYG editor.
             */
            $assetsVersionBaseUrl   = $this->assetHelper->getUrl('/');
            $assetsVersionFormatted = substr($assetsVersionBaseUrl, strrpos($assetsVersionBaseUrl, '/') + 1);
        }

        $defaultWysiwygOptions = [
            'plugins'            => self::$defaultPlugins,
            'toolbar_type'       => self::TOOLBAR_DEFAULT,
            'valid_elements'     => implode(',', $this->htmlTagProvider->getAllowedElements('default')),
            'menubar'            => false,
            'elementpath'        => false,
            'branding'           => false,
            'relative_urls'      => false,
            'remove_script_host' => false,
            'convert_urls'       => true,
            'cache_suffix'       => $assetsVersionFormatted,
            'document_base_url'  => $assetsVersionBaseUrl,
            'paste_data_images'  => false,
            'assets_base_url'    => $assetsBaseUrl
        ];

        $defaults = [
            'wysiwyg_enabled' => (bool) $this->configManager->get('oro_form.wysiwyg_enabled'),
            'wysiwyg_options' => $defaultWysiwygOptions,
            'page-component'  => [
                'module'  => 'oroui/js/app/components/view-component',
                'options' => [
                    'view' => 'oroform/js/app/views/wysiwig-editor/wysiwyg-editor-view',
                    'content_css' => $assetsBaseUrl . 'build/admin/tinymce/wysiwyg-editor.css'
                ]
            ],
        ];

        $resolver->setDefaults($defaults);

        $resolver->setNormalizer(
            'wysiwyg_options',
            function (Options $options, $wysiwygOptions) use ($defaultWysiwygOptions) {
                if (!empty($wysiwygOptions['toolbar'])) {
                    $wysiwygOptions = array_merge($defaultWysiwygOptions, $wysiwygOptions);
                    unset($wysiwygOptions['toolbar_type']);

                    return $wysiwygOptions;
                }

                if (empty($wysiwygOptions['toolbar_type'])
                    || !array_key_exists($wysiwygOptions['toolbar_type'], self::$toolbars)
                ) {
                    $toolbarType = self::TOOLBAR_DEFAULT;
                } else {
                    $toolbarType = $wysiwygOptions['toolbar_type'];
                }
                $wysiwygOptions['toolbar'] = self::$toolbars[$toolbarType];

                $wysiwygOptions = array_merge($defaultWysiwygOptions, $wysiwygOptions);
                unset($wysiwygOptions['toolbar_type']);

                return $wysiwygOptions;
            }
        )
        ->setNormalizer(
            'attr',
            function (Options $options, $attr) {
                $pageComponent  = $options['page-component'];
                $wysiwygOptions = (array) $options['wysiwyg_options'];

                $pageComponent['options']            = array_merge($pageComponent['options'], $wysiwygOptions);
                $pageComponent['options']['enabled'] = (bool) $options['wysiwyg_enabled'];

                $attr['data-page-component-module']  = $pageComponent['module'];
                $attr['data-page-component-options'] = json_encode($pageComponent['options']);

                return $attr;
            }
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextareaType::class;
    }
}
