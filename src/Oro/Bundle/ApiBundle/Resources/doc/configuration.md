Configuration
-------------

By default API for all entities, except custom entities, dictionaries and enumerations are disabled. To enable API for any entity you can use `Resources/config/oro/api.yml` file. For example, to make `Acme\Bundle\ProductBundle\Product` entity accessible through API you can write the following configuration:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product: ~
```
