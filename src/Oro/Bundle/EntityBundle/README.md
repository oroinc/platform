OroEntityBundle
===============

Entity and entity field selectors, extended functionality of Doctrine entity manager.

**Entity Manager**

In order to extend some native Doctrine Entity Manager functionality a new class `OroEntityManager` was implemented.
In case any other modification are required, your class should extend `OroEntityManager` instead of Doctrine Entity Manager.

**Filter Collection**

Standard Doctrine filter collection implementation allows to add/enable sql filter by passing class name only.
It makes impossible to inject custom services into filters. To provide this functionality,
a new `FilterCollection` class was implemented that allows to add filter objects directly.

Necessary filters can be automatically added to the filters collection by adding `oro_entity.orm.sql_filter` tag:

```yml
oro_security.orm.ownership_sql_filter:
    class: %oro_security.orm.ownership_sql_filter.class%
    arguments:
       - @doctrine.orm.entity_manager
    tags:
       - { name: oro_entity.orm.sql_filter, filter_name: ownershipFilter, enabled: true }
```

where

 - **filter_name** - required filter name,
 - **enbaled** - flag, if the filter must be enabled, by default filters are disabled

## Doctrine field types ##

Some entities have fields which data is money or percents.

For this data was created new field types - money and percent.

**money** field type allow to store money data. It's an alias to decimal(19,4) type.

You can use this field type like:

```php
    /**
     * @var decimal
     *
     * @ORM\Column(name="tax_amount", type="money")
     */
    protected $taxAmount;
```

**percent** field type allow to store percent data. It's an alias to float type.

You can use this field type like:

```php
    /**
     * @var float
     *
     * @ORM\Column(name="percent_field", type="percent")
     */
    protected $percentField;
```

This two data types are available in extend fields. You can create new fields with this types. Additionally in view pages, in grids and in edit pages this fields will be automatically formatted with currency or percent formatters.

In grid, for percent data type will be automatically generated percent filter.


## Entity name resolver and providers ##

**Entity name resolver**

The [Entity Name Resolver](./Provider/EntityNameResolver.php) service has been introduced to make the configuring of entity name formatting more flexible.

It provides two functions for getting the entity name:

- string *public* *getName*(object *entity*[, string *format*, string *locale*])

This method can be used to get a text representation of an entity formatted according to the format notation passed (e.g. "full", "short", etc.). If the format is not specified, the default one will be used.

To format the text representation using a specific locale, the *locale* parameter may be passed.

- string *public* *getNameDQL*(string *className*, string *alias*[, string *format*, string *locale*])

This method is useful for getting a DQL expression that can be used to get a text representation of the given type of entities formatted according to the format notation passed (e.g. "full", "short", etc.). If the format is not specified, the default one will be used.

To get a text representation using a specific locale, the *locale* parameter may be passed.

Example of usage:
```php
$entityNameResolver = $container->get('oro_entity.entity_name_resolver');
$user->setFirstName('John');
$user->setLastName('Doe');
echo $entityNameResolver->getName($user); // outputs: John Doe
echo $entityNameResolver->getNameDQL('Oro\Bundle\UserBundle\Entity\User', 'u'); // outputs: CONCAT(u.firstName, CONCAT(u.lastName, ' ')
```

The available entity formats can be configured in the `entity_name_formats` section of `Resources/config/oro/entity.yml` file:

```yaml
oro_entity:
    entity_name_formats:
        full:
            fallback: short
        short: ~
```

Note that it is possible to specify the fallback format for the entity that will be used when the given format is not supported.

**Entity name providers**

The Entity Name Resolver does not know how to get the entity name by itself but instead it expects to have a collection of Entity Name Providers that will do the job.
The first provider that is able to return a reliable result wins. The rest of providers will not be asked.

To create an Entity Name Provider you should implement the [EntityNameProviderInterface](./Provider/EntityNameProviderInterface.php):
```php
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

class FullNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($format === self::FULL && $this->isFullFormatSupported(get_class($entity))) {
            // return entity format
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if ($format === self::FULL && $this->isFullFormatSupported($className)) {
            // return DQL to get entity format
        }

        return false;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function isFullFormatSupported($className)
    {
        // check if $className supports full name formatting, e.g. implements some required interfaces
    }
}
```

Note that if the provider cannot return a reliable result, FALSE should be returned to keep looking in the other providers in chain.

Entity name providers are registered in the DI container by `oro_entity.name_provider` tag:

```yml
    oro_entity.entity_name_provider.default:
        class: %oro_entity.entity_name_provider.default.class%
        public: false
        arguments:
            - @doctrine
        tags:
            - { name: oro_entity.name_provider, priority: 100 }
```

The priority can be specified to move the provider up or down the providers chain.
