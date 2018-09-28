# Dictionaries

Dictionary entities are responsible for storing a predefined set of values of a certain type and their translations. They values within a dictionary can have a priority or some other data.

## Automatic creation of REST API for dictionaries

REST API resources for viewing dictionary values are created automatically and they are accessible by the following URL:

`/api/{dictionary_plural_alias}`. For example `/api/casestatuses`.

Please refer to [entity aliases](entity_aliases.md) to get a better understanding how the aliases are generated.

**Dictionary types supported out-of-the-box**

REST API resources are created automatically for the following types of dictionaries:

- Non-translatable dictionary
- Translatable dictionary (implements `Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation`)
- Personal translatable dictionary (implements `Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation`)
- Enum (Option set)

***Creating a custom dictionary type***

If you have some group of entities which can be classified as a dictionary, but by some reason they are not included in the `dictionary` group in entity configuration, and you want to have its entities added to the dictionary REST API, you need to do two things.

First, you should create a dictionary value list provider implementing the [DictionaryValueListProviderInterface](./../../Provider/DictionaryValueListProviderInterface.php) interface.

And second, you should register your provider service in the DI container by the tag `oro_entity.dictionary_value_list_provider`:

```yml
    oro_entity.dictionary_value_list_provider.default:
        class: %oro_entity.dictionary_value_list_provider.default.class%
        public: false
        arguments:
            - @oro_entity_config.config_manager
            - @doctrine
        tags:
            - { name: oro_entity.dictionary_value_list_provider, priority: -100 }
```

Please, note that you can specify the priority for the dictionary value list provider. The bigger the priority number is, the earlier the provider will be executed.
If there are more than one dictionary value list providers that support the same type of dictionary, only the one with the greater priority will be executed. The priority value is optional and defaults to 0.
