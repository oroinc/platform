Configuration
-------------

All entities, except custom entities, dictionaries and enumerations are not accessible through Data API. To allow using of an entity in Data API you can use `Resources/config/oro/api.yml` file. For example, to make `Acme\Bundle\ProductBundle\Product` entity available through Data API you can write the following configuration:

```yaml
oro_api:
    entities:
        Acme\Bundle\ProductBundle\Product: ~
```
