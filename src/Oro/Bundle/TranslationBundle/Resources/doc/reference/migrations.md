Migrations
----------

**Class:** `Oro\Bundle\TranslationBundle\Migration\DeleteTranslationKeysQuery`

Provide useful way to delete custom translation keys during migration.

**Arguments**:
* `domain` (string) - domain of translation keys to process removal by
* `translationKeys` (array) - an array of translation key strings to remove

**Example**:
   To remove custom keys in your migration you can use `Oro\Bundle\MigrationBundle\Migration\QueryBag`s method `addQuery`
   
```PHP
    $queryBag->addQuery(
        new Oro\Bundle\TranslationBundle\Migration\DeleteTranslationKeysQuery(
            'custom_domain',
            ['translation.key1.to.remove', 'translation.key2.to.remove ]
        )
    );
```

A `Oro\Bundle\MigrationBundle\Migration\QueryBag` instance is usually available in `Oro\Bundle\MigrationBundle\Migration\Migration::up` method as second argument.

See [migration details](../../../../MigrationBundle/README.md) for more info.