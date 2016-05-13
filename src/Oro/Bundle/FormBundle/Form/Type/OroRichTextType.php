<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Asset\Packages as AssetHelper;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;

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

    /** @var string */
    protected $cacheDir;

    /**
     * @url http://www.tinymce.com/wiki.php/Configuration:toolbar
     * @var array
     */
    public static $toolbars = [
        self::TOOLBAR_SMALL  => ['undo redo | bold italic underline | bullist numlist link | bdesk_photo | fullscreen'],
        self::TOOLBAR_DEFAULT => [
            'undo redo | bold italic underline | forecolor backcolor | bullist numlist | link | code | bdesk_photo 
             | fullscreen'
        ],
        self::TOOLBAR_LARGE => [
            'undo redo | bold italic underline | forecolor backcolor | bullist numlist | link | code | bdesk_photo 
            | fullscreen'
        ],
    ];

    /**
     * @var array
     */
    public static $defaultPlugins = ['textcolor', 'code', 'link', 'bdesk_photo', 'fullscreen'];

    /**
     * @param ConfigManager   $configManager
     * @param HtmlTagProvider $htmlTagProvider
     * @param string          $cacheDir
     */
    public function __construct(ConfigManager $configManager, HtmlTagProvider $htmlTagProvider, $cacheDir = null)
    {
        $this->configManager   = $configManager;
        $this->htmlTagProvider = $htmlTagProvider;
        $this->cacheDir        = $cacheDir;
    }

    /**
     * @param AssetHelper $assetHelper
     */
    public function setAssetHelper(AssetHelper $assetHelper)
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

        $transformer = new SanitizeHTMLTransformer($allowableTags, $this->cacheDir);
        $builder->addModelTransformer($transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
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
            'skin_url'           => 'bundles/oroform/css/tinymce',
            'valid_elements'     => implode(',', $this->htmlTagProvider->getAllowedElements()),
            'menubar'            => false,
            'statusbar'          => false,
            'relative_urls'      => false,
            'remove_script_host' => false,
            'convert_urls'       => true,
            'cache_suffix'       => $assetsVersionFormatted,
            'document_base_url'  => $assetsVersionBaseUrl
        ];

        $defaults = [
            'wysiwyg_enabled' => (bool) $this->configManager->get('oro_form.wysiwyg_enabled'),
            'wysiwyg_options' => $defaultWysiwygOptions,
            'page-component'  => [
                'module'  => 'oroui/js/app/components/view-component',
                'options' => [
                    'view'        => 'oroform/js/app/views/wysiwig-editor/wysiwyg-editor-view',
                    'content_css' => 'bundles/oroform/css/wysiwyg-editor.css',
                ]
            ],
        ];

        $resolver->setDefaults($defaults);
        $resolver->setNormalizers(
            [
                'wysiwyg_options' => function (Options $options, $wysiwygOptions) use ($defaultWysiwygOptions) {
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
                },
                'attr'            => function (Options $options, $attr) {
                    $pageComponent  = $options->get('page-component');
                    $wysiwygOptions = (array) $options->get('wysiwyg_options');

                    $pageComponent['options']            = array_merge($pageComponent['options'], $wysiwygOptions);
                    $pageComponent['options']['enabled'] = (bool) $options->get('wysiwyg_enabled');

                    $attr['data-page-component-module']  = $pageComponent['module'];
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
