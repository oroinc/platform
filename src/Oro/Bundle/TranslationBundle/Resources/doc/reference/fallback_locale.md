Locale Fallback Mechanism
=============


### Classes Description

#### TranslationBundle \ Strategy \ DefaultTranslationStrategy

This class implements TranslationStrategyInterface for default strategy mechanism;

Constants:

* **NAME** - default identifier of the strategy;

Methods:

* **getName** - public method return default strategy;
* **getLocaleFallbacks** - public method, for default strategy has only one locale and no fallbacks;


#### TranslationBundle \ Strategy \ TranslationStrategyInterface**

Methods:

* **getName** - method return text identifier of the strategy;
* **getLocaleFallbacks** - this method return tree of locale fallbacks. Example below;


#### TranslationBundle \ Strategy \ TranslationStrategyProvider

Translation provider, set and return TranslationStrategyInterface.

Methods:

* **getStrategy** - method return TranslationStrategyInterface;
* **setStrategy** - method form TranslationStrategyInterface, sets strategy property;


### Example fallback mechanism of result


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

if locale 'en_MX', returns array;
```
['en_US', 'en']
```

if locale 'en', returns empty array;
```
[]
```
