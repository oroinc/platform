## Entity class name provider

The goal of this service is to provide human-readable representation in **English** of an entity class name. This may be helpful if you need to generate some kind of documentation for a functionality that generated automatically. For example, OroPlatform uses this provider to generate a description of REST API resources that is generated on the fly. See [DictionaryEntityApiDocHandler](./../../Routing/DictionaryEntityApiDocHandler.php) for details.

**Interface of an entity class name provider**

The [entity class name provider](./../../Provider/ChainEntityClassNameProvider.php) service is a "chain" service. It means that it works by asking a set of prioritized providers to get to get a human-readable representation of an entity class name. Each child service must implement [EntityClassNameProviderInterface](./../../Provider/EntityClassNameProviderInterface.php). This interface declare the following methods:

- *getEntityClassName* - returns a human-readable representation for an entity class.
- *getEntityClassPluralName* - returns a human-readable representation in plural for an entity class.

**Create custom entity class name provider**

To create own provider just create a class implementing  [EntityClassNameProviderInterface](./../../Provider/EntityClassNameProviderInterface.php) and register it in DI container with the tag **oro_entity.class_name_provider**. Also you can use existing [abstract provider](./../../Provider/AbstractEntityClassNameProvider.php) as a super class for your provider.

```php
use Oro\Bundle\EntityBundle\Provider\AbstractEntityClassNameProvider;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;

class AcmeClassNameProvider extends AbstractEntityClassNameProvider implements EntityClassNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClassName($entityClass)
    {
        // add your implementation here
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassPluralName($entityClass)
    {
        // add your implementation here
    }
}
```

```yml
    entity_class_name_provider.acme:
        class: Acme\Bundle\TestBundle\Provider\AcmeClassNameProvider
        public: false
        tags:
            - { name: oro_entity.name_provider, priority: 100 }
```

The priority can be specified to move the provider up or down the providers chain. The bigger the priority number is, the earlier the provider will be executed. The priority value is optional and defaults to 0.
