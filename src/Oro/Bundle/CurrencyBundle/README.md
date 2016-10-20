Oro\Bundle\CurrencyBundle\OroCurrencyBundle
===================================================

Description:
------------

This bundle provides a way to handle prices and currencies.

Bundle responsibilities:
------------------------

Bundle provides forms for setting price with currency to another objects (for example product).
Also it includes available currencies management from System configuration.

Expected dependencies:
----------------------

Oro\Bundle\ConfigBundle\Config\ConfigManager
Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder
Oro\Bundle\LocaleBundle\Formatter\NumberFormatter
Oro\Bundle\LocaleBundle\Model\LocaleSettings
Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase
Symfony\Component\Config\Definition\Builder\TreeBuilder
Symfony\Component\Config\Definition\ConfigurationInterface
Symfony\Component\Config\FileLocator
Symfony\Component\DependencyInjection\ContainerBuilder
Symfony\Component\DependencyInjection\Loader
Symfony\Component\Form
Symfony\Component\HttpKernel\Bundle\Bundle
Symfony\Component\HttpKernel\DependencyInjection\Extension
Symfony\Component\Intl\Intl
Symfony\Component\OptionsResolver\Options
Symfony\Component\OptionsResolver\OptionsResolverInterface
Symfony\Component\PropertyAccess\PropertyAccess
Symfony\Component\Validator\Validation
Twig_SimpleFilter
Intl
