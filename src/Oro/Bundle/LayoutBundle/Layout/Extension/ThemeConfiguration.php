<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Bundle\ThemeBundle\Form\Provider\ConfigurationBuildersProvider;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for configuration that is loaded from the following files:
 * * Resources/views/layouts/{folder}/theme.yml
 * * Resources/views/layouts/{folder}/config/assets.yml
 * * Resources/views/layouts/{folder}/config/images.yml
 * * Resources/views/layouts/{folder}/config/page_templates.yml
 */
class ThemeConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'themes';

    public const AUTO = 'auto';

    public const OPTION_KEY_DELIMITER = '__';

    /** @var ThemeConfigurationExtensionInterface[] */
    private array $extensions = [];

    public function __construct(private ConfigurationBuildersProvider $configurationBuildersProvider)
    {
    }

    public function addExtension(ThemeConfigurationExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * @return string[]
     */
    public function getAdditionalConfigFileNames(): array
    {
        $fileNames = [[
            'assets.yml',
            'images.yml',
            'page_templates.yml'
        ]];
        foreach ($this->extensions as $extension) {
            $extensionFileNames = $extension->getConfigFileNames();
            if ($extensionFileNames) {
                $fileNames[] = $extensionFileNames;
            }
        }
        return array_merge(...$fileNames);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $configTreeBuilder = new TreeBuilder('config');
        $configurationTreeBuilder = new TreeBuilder('configuration');
        $configurationNode = $configurationTreeBuilder->getRootNode();
        $configurationNode->info(
            'Configuration that defines which parameters a storefront theme configurable by a store owner.'
        );
        $configNode = $configTreeBuilder->getRootNode();
        $configNode->info('Layout theme additional config')->end();
        // Allow extra configuration keys to be present in this configuration node.
        // This is needed to give other bundles ability to declare and add custom configuration.
        $configNode->ignoreExtraKeys(false);

        $rootNode
            ->useAttributeAsKey('name')
            ->normalizeKeys(false)
            ->prototype('array')
                ->children()
                    ->scalarNode('label')
                        ->info('The label is displayed in the theme management UI. Can be empty for "hidden" themes')
                        ->isRequired()
                    ->end()
                    ->scalarNode('description')
                        ->info('The description is displayed in the theme selection UI. Can be empty')
                    ->end()
                    ->scalarNode('icon')
                        ->info('The icon is displayed in the UI')
                    ->end()
                    ->scalarNode('favicons_path')
                        ->info('The path to favicons')
                    ->end()
                    ->arrayNode('image_placeholders')
                        ->info('The images which will be displayed while no image')
                        ->prototype('scalar')->end()
                    ->end()
                    ->scalarNode('logo')
                        ->info('The logo image is displayed in the UI')
                    ->end()
                    ->scalarNode('logo_small')
                        ->info('The logo small image is displayed in the UI')
                    ->end()
                    ->scalarNode('screenshot')
                        ->info('The screenshot image is used in theme management UI for the theme preview')
                    ->end()
                    ->booleanNode('rtl_support')
                        ->info('Defines whether Theme supports RTL and additional *.rtl.css files have to be build')
                    ->end()
                    ->booleanNode('svg_icons_support')
                        ->info(
                            'Defines whether Theme supports SVG icons. '
                            . 'Default value will be inherited from the parent themes if any, otherwise - false.'
                        )
                    ->end()
                    ->scalarNode('directory')
                        ->info('The directory name where to look up for layout updates. By default theme identifier')
                    ->end()
                    ->scalarNode('parent')
                        ->info('The identifier of the parent theme')
                    ->end()
                    ->arrayNode('groups')
                        ->info('Layout groups for which the theme is applicable')
                        ->example('[main, embedded_forms, frontend]')
                        ->prototype('scalar')->end()
                        ->requiresAtLeastOneElement()
                    ->end()
                    ->arrayNode('resolve_extra_paths')
                        ->info('Extra js paths which will be included in module builder')
                        ->example('[/bundles/orothemedefault51]')
                        ->prototype('scalar')->end()
                        ->requiresAtLeastOneElement()
                    ->end()
                    ->arrayNode('extra_js_builds')
                        ->info('Additional js builds that can be used for certain pages')
                        ->example('[home, landing, product]')
                        ->prototype('scalar')
                            ->cannotBeEmpty()
                            ->validate()
                                ->always(function ($name) {
                                    if (str_contains($name, '-')) {
                                        $message = sprintf('cannot contain "-" in the value, but got "%s".', $name);
                                        throw new \Exception($message);
                                    }
                                    return $name;
                                })
                            ->end()
                        ->end()
                    ->end()
                    ->append($configurationNode)
                    ->append($configNode)
                ->end()
            ->end();

        $this->appendConfigurationNode($configurationNode->children());
        $this->appendConfigNodes($configNode->children());

        return $treeBuilder;
    }

    private function appendConfigNodes(NodeBuilder $configNode)
    {
        $this->appendAssets($configNode);
        $this->appendImages($configNode);
        $this->appendPageTemplates($configNode);
        foreach ($this->extensions as $extension) {
            $extension->appendConfig($configNode);
        }
    }

    private function appendAssets(NodeBuilder $configNode): void
    {
        $configNode->arrayNode('assets')
            ->useAttributeAsKey('name')
            ->normalizeKeys(false)
            ->prototype('array')
                ->children()
                    ->arrayNode('inputs')
                        ->info('Input assets list')
                        ->prototype('variable')->end()
                    ->end()
                    ->arrayNode('auto_rtl_inputs')
                        ->info('List of file masks for inputs that has to be processed with RTL plugin')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('filters')
                        ->info('Filters to manipulate input assets')
                        ->prototype('scalar')->end()
                    ->end()
                    ->scalarNode('output')
                        ->info('Output asset')
                    ->end()
                ->end()
            ->end();
    }

    private function appendImages(NodeBuilder $configNode): void
    {
        $widthHeightValidator = function ($value) {
            return null !== $value && !is_int($value) && self::AUTO !== $value;
        };

        $configNode->arrayNode('images')
            ->children()
                ->arrayNode('types')
                ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('label')->cannotBeEmpty()->end()
                            ->scalarNode('max_number')->defaultNull()->end()
                            ->arrayNode('dimensions')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('dimensions')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->validate()
                            ->ifTrue(function (array $dimension) {
                                return self::AUTO === $dimension['width'] && self::AUTO === $dimension['height'];
                            })
                            ->thenInvalid('Either width or height can be set to \'auto\', not both.')
                        ->end()
                        ->children()
                            ->scalarNode('width')
                                ->validate()
                                    ->ifTrue($widthHeightValidator)
                                    ->thenInvalid('Width value can be null, \'auto\' or integer only')
                                ->end()
                                ->isRequired()
                            ->end()
                            ->scalarNode('height')
                                ->validate()
                                    ->ifTrue($widthHeightValidator)
                                    ->thenInvalid('Height value can be null, \'auto\' or integer only')
                                ->end()
                                ->isRequired()
                            ->end()
                            ->arrayNode('options')
                                ->prototype('variable')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function appendPageTemplates(NodeBuilder $configNode): void
    {
        $configNode->arrayNode('page_templates')
            ->children()
                ->arrayNode('templates')
                    ->info('List of page templates')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('route_name')->cannotBeEmpty()->end()
                            ->scalarNode('key')->cannotBeEmpty()->end()
                            ->scalarNode('label')->cannotBeEmpty()->end()
                            ->scalarNode('description')->defaultNull()->end()
                            ->scalarNode('screenshot')->defaultNull()->end()
                            ->booleanNode('enabled')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('titles')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                ->end()
            ->end();
    }

    private function appendConfigurationNode(NodeBuilder $configNode): void
    {
        $configNode
            ->arrayNode('sections')
                ->info(
                    <<<INFO
Groups theme configuration parameters, options related to the section - will be displayed in a separate tab.
INFO
                )
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('label')
                            ->info('The section label is displayed in the theme configuration UI.')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('options')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('label')
                                        ->info(
                                            <<<INFO
The option label is displayed in the theme configuration UI.
INFO
                                        )
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->enumNode('type')
                                        ->info('The type of the input in theme configuration UI.')
                                        ->values($this->configurationBuildersProvider->getConfigurationTypes())
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('default')
                                        ->info(
                                            <<<INFO
The value is displayed by default in the theme configuration UI.
INFO
                                        )
                                    ->end()
                                    ->arrayNode('values')
                                        ->info('Available input values')
                                        ->normalizeKeys(false)
                                        ->prototype('scalar')
                                            ->cannotBeEmpty()
                                        ->end()
                                    ->end()
                                    ->arrayNode('attr')
                                        ->normalizeKeys(false)
                                        ->info("Attributes to be added to the input html tag")
                                        ->prototype('scalar')
                                            ->cannotBeEmpty()
                                        ->end()
                                    ->end()
                                    ->arrayNode('options')
                                        ->info("Options to be added to the form type")
                                        ->prototype('variable')->end()
                                    ->end()
                                    ->arrayNode('previews')
                                        ->info(
                                            <<<INFO
Images that will illustrate UI changes if this option is selected.
INFO
                                        )
                                        ->normalizeKeys(false)
                                        ->prototype('scalar')
                                            ->cannotBeEmpty()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    public static function buildOptionKey(string $sectionKey, string $optionKey): string
    {
        return sprintf('%s' . self::OPTION_KEY_DELIMITER . '%s', $sectionKey, $optionKey);
    }
}
