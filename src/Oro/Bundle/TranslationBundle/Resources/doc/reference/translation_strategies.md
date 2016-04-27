Translation Strategies
======================




### Classes Description

#### TranslationBundle\Strategy\TranslationStrategyInterface**

Main interface for translation strategies.

Methods:

* **getName** - returns text identifier of the strategy;
* **getLocaleFallbacks** - returns tree of locale fallbacks, see example below:

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


#### TranslationBundle\Strategy\DefaultTranslationStrategy

Implementation of TranslationStrategyInterface to handle default one-locale translation fallback.


#### TranslationBundle\Strategy\TranslationStrategyProvider

Main purpose of this class is storing of current translation strategy and performing additional manipulations with it. 

Methods:

* **getStrategy** - returns current strategy;
* **setStrategy** - sets specified strategy as current;
