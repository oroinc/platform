Translation Strategies
======================

Translation bundle provides mechanism of translation strategies to handle translation fallbacks.
Each strategy provides locale fallback tree that describes which locales must be used as fallback locale
for each source fallback. Here is example of such tree:

```
[
    'en' => [
        'en_US' => [
            'en_CA' => [],
            'en_MX' => [],
         ],
        'en_CA' => [],
        'en_GB  => [],
        ...
    ],
    ...
]
```

Current strategy can be extracted from strategy provider - this class is used to store selected strategy and
perform some additional manipulations with it. Translator uses strategy provider and current strategy to handle
translation fallbacks.


### Classes Description


#### TranslationBundle\Strategy\TranslationStrategyInterface

Main interface for translation strategies.

Methods:

* **getName** - returns text identifier of the strategy;
* **getLocaleFallbacks** - returns tree of locale fallbacks.


#### TranslationBundle\Strategy\DefaultTranslationStrategy

Implementation of TranslationStrategyInterface to handle default one-locale translation fallback.


#### TranslationBundle\Strategy\TranslationStrategyProvider

Main purpose of this class is storing of current translation strategy and performing additional manipulations with it. 

Methods:

* **getStrategy** - returns current strategy;
* **setStrategy** - sets specified strategy as current;
* **getFallbackLocales** - returns list of allowed fallback locales for specified strategy and source locale;
* **getAllFallbackLocales** - returns list of all fallback locales for specified strategy.
