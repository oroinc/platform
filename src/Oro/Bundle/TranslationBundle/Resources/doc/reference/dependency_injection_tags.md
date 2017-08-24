The Dependency Injection Tags
=============================

| Type name | Usage |
|-----------|-------|
| [oro_translation.extension.packages_provider](#oro_translationextensionpackages_provider) | Registers provider to get installed translation packages and their pathes |
| [oro_translation.extension.translation_strategy](#oro_translationextensiontranslation_strategy) | Registers strategy for providing translation locale fallbacks |

oro_translation.extension.packages_provider
-------------------------------------------
Provide installed translation package. Provider must implement [TranslationPackagesProviderExtensionInterface](../../../Provider/TranslationPackagesProviderExtensionInterface.php) or use `oro_translation.extension.transtation_packages_provider.abstract` as a parent service.

oro_translation.extension.translation_strategy
----------------------------------------------
Provide translation locale fallbacks. Strategy must implement [TranslationStrategyInterface](../../../Strategy/TranslationStrategyInterface.php).
