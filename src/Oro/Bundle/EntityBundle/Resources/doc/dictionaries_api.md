## Automatic creation of REST API for dictionaries ##

Dictionary entities are responsible for storing a predefined set of values of a certain type and their translations. They values within a dictionary can have a priority or some other data.
Nevertheless, there is an automatic tool that creates a REST API resource for viewing dictionary values, which is accessible by the following URL:

`/api/rest/{version}/{dictionary_plural_alias}.{_format}`. For example `/api/rest/latest/casestatuses.json`.

All generated REST API resources for dictionary values are processed by a single [DictionaryController](./../../Controller/Api/Rest/DictionaryController.php).

Please refer to the entity alias [document](entity_aliases.md) to get a better understanding how entity aliases are generated.

**Dictionary types supported out-of-the-box**

REST API resources are created automatically for the following types of dictionaries:
- Non-translatable dictionary
- Translatable dictionary (implements `Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation`)
- Personal translatable dictionary (implements `Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation`)
- Enum (Option set)


**Customizing dictionary API**

There are two approaches to customize the dictionary API basing on your needs

***Creating a custom controller***

In case if you need to customize API for just one dictionary entity, the easiest way will be to create a new controller that will override the automatically generated path. For example:

```php
/**
 * @RouteResource("casestatus")
 * @NamePrefix("oro_api_")
 */
class CaseStatusesController extends RestGetController
{
    /**
     * Get case statuses
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to 10. Set -1 to get all items."
     * )
     * @QueryParam(
     *      name="locale",
     *      requirements=".+",
     *      nullable=true,
     *      description="The preferred locale for dictionary values. Falls back to the default locale."
     * )
     * @ApiDoc(
     *      description="Get case statuses",
     *      resource=true
     * )
     * @return Response
     */
    public function cgetAction()
    {
        ...
    }
}
```

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


***Troubleshooting***

In case if the REST API resource for your custom dictionary type is not available, do the following:

- Make sure that `getSupportedEntityClasses()` method in the corresponding dictionary value provider returns your dictionary entity class
- Find the plural alias of your dictionary entity using `php app/console oro:entity-alias:debug` CLI command
- Check if this alias is present in the routing list with the help of `php app/console oro:entity-alias:debug` CLI command.
The found route name should start with the `oro_api_get_dictionary_values_` prefix, e.g.: `oro_api_get_dictionary_values_auto_790    GET    ANY    ANY  /api/rest/{version}/casestatuses.{_format}`
