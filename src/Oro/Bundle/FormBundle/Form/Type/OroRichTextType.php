<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Templating\Asset\PackageInterface;

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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaults = [
            'wysiwyg_enabled' => (bool)$this->configManager->get('oro_form.wysiwyg_enabled'),
            'wysiwyg_options' => [
                'plugins' => ['textcolor', 'code'],
                'toolbar' => ['undo redo | bold italic underline | forecolor backcolor | bullist numlist | code'],
                'menubar' => false,
                'statusbar' => false
            ],
            'attr' => [
                'data-page-component-module' => 'oroui/js/app/components/view-component',
                'data-page-component-options' => [
                    'view' => 'oroform/js/app/views/wysiwig-editor/wysiwyg-editor-view'
                ]
            ]
        ];

        if ($this->assetHelper) {
            $defaults['attr']['data-page-component-options']['content_css'] = $this->assetHelper
                ->getUrl('bundles/oroform/css/wysiwyg-editor.css');
        }

        $resolver->setDefaults($defaults);
        $resolver->setNormalizers(
            [
                'attr' => function (Options $options, $attr) {
                    $attr['data-page-component-options']['enabled'] = (bool)$options->get('wysiwyg_enabled');

                    $attr['data-page-component-options'] = array_merge(
                        $attr['data-page-component-options'],
                        (array)$options->get('wysiwyg_options')
                    );
                    $attr['data-page-component-options'] = json_encode($attr['data-page-component-options']);

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
