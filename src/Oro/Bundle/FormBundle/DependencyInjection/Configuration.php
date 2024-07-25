<?php

namespace Oro\Bundle\FormBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Oro FormBundle Configuration
 */
class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_form';
    public const WYSIWYG_ENABLED = 'wysiwyg_enabled';
    public const ENABLED_CAPTCHA = 'enabled_captcha';
    public const CAPTCHA_SERVICE = 'captcha_service';
    public const CAPTCHA_PROTECTED_FORMS = 'captcha_protected_forms';

    public const RECAPTCHA_PUBLIC_KEY = 'recaptcha_public_key';
    public const RECAPTCHA_PRIVATE_KEY = 'recaptcha_private_key';
    public const RECAPTCHA_MINIMAL_ALLOWED_SCORE = 'recaptcha_minimal_allowed_score';

    public const HCAPTCHA_PUBLIC_KEY = 'hcaptcha_public_key';
    public const HCAPTCHA_PRIVATE_KEY = 'hcaptcha_private_key';

    public const TURNSTILE_PUBLIC_KEY = 'turnstile_public_key';
    public const TURNSTILE_PRIVATE_KEY = 'turnstile_private_key';

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                self::WYSIWYG_ENABLED => ['value' => true, 'type' => 'bool'],
                self::ENABLED_CAPTCHA => [
                    'value' => false,
                    'type' => 'boolean'
                ],
                self::CAPTCHA_SERVICE => [
                    'value' => 'recaptcha',
                    'type' => 'string'
                ],
                self::CAPTCHA_PROTECTED_FORMS => [
                    'value' => [],
                    'type' => 'array'
                ],
                self::RECAPTCHA_PUBLIC_KEY => [
                    'value' => '',
                    'type' => 'string'
                ],
                self::RECAPTCHA_PRIVATE_KEY => [
                    'value' => '',
                    'type' => 'string'
                ],
                self::RECAPTCHA_MINIMAL_ALLOWED_SCORE => [
                    'value' => '0.5',
                    'type' => 'integer'
                ],
                self::HCAPTCHA_PUBLIC_KEY => [
                    'value' => '',
                    'type' => 'string'
                ],
                self::HCAPTCHA_PRIVATE_KEY => [
                    'value' => '',
                    'type' => 'string'
                ],
                self::TURNSTILE_PUBLIC_KEY => [
                    'value' => '',
                    'type' => 'string'
                ],
                self::TURNSTILE_PRIVATE_KEY => [
                    'value' => '',
                    'type' => 'string'
                ]
            ]
        );

        $rootNode
            ->children()
                ->arrayNode('html_purifier_modes')
                    ->info('Describes scopes and scope rules for HTMLPurifier')
                    ->useAttributeAsKey('default')
                    ->arrayPrototype()
                        ->info('Collection of scopes that defines the rules for HTMLPurifier')
                        ->children()
                            ->scalarNode('extends')
                                ->info('Extends configuration from selected scope')
                                ->example('default')
                                ->defaultNull()
                            ->end()
                            ->arrayNode('allowed_rel')
                                ->beforeNormalization()
                                ->ifArray()
                                ->then(static function (array $allowedRel) {
                                    return array_fill_keys($allowedRel, true);
                                })
                                ->end()
                                ->info(
                                    'List of allowed forward document relationships in the rel attribute ' .
                                    'for HTMLPurifier.'
                                )
                                ->example(['nofollow', 'alternate'])
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('allowed_iframe_domains')
                                ->info(
                                    'Only these domains will be allowed in iframes ' .
                                    '(in case iframes are enabled in allowed elements)'
                                )
                                ->example(['youtube.com/embed/', 'player.vimeo.com/video/'])
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('allowed_uri_schemes')
                                ->info('Allowed URI schemes for HTMLPurifier')
                                ->example(['http', 'https', 'mailto', 'ftp', 'data', 'tel'])
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('allowed_html_elements')
                                ->info('Allowed elements and attributes for HTMLPurifier')
                                ->arrayPrototype()
                                    ->info('Collection of allowed HTML elements for HTMLPurifier')
                                    ->children()
                                        ->arrayNode('attributes')
                                            ->info('Collection of allowed attributes for described HTML tag')
                                            ->example(['cellspacing', 'cellpadding', 'border', 'align', 'width'])
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->booleanNode('hasClosingTag')
                                            ->info('Is HTML tag has closing end tag or not')
                                            ->defaultTrue()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    public static function getConfigKey(string $key): string
    {
        return TreeUtils::getConfigKey(self::ROOT_NODE, $key);
    }
}
