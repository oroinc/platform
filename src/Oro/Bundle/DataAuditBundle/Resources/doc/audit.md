How to add new auditable types
==============================

* first you need to register new type in your bundle's boot method

```php
    <?php

    use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;

    class MyBundle extends Bundle
    {
        public function boot()
        {
            /**
             * You can also use AuditFieldTypeRegistry::overrideType to replace existing type
             * But make sure you move old data into new columns
             */
            AuditFieldTypeRegistry::addType($doctrineType = 'datetimetz', $auditType = 'datetimetz');
        }
    }
```

* then you have to create migration which will add columns in AuditField entity

```php
    <?php

    use Doctrine\DBAL\Schema\Schema;

    use Oro\Bundle\DataAuditBundle\Migration\Extension\AuditFieldExtension;
    use Oro\Bundle\DataAuditBundle\Migration\Extension\AuditFieldExtensionAwareInterface;
    use Oro\Bundle\MigrationBundle\Migration\Migration;
    use Oro\Bundle\MigrationBundle\Migration\QueryBag;

    class MyMigration implements Migration, AuditFieldExtensionAwareInterface
    {
        /** @var AuditFieldExtension */
        private $auditFieldExtension;

        public function setAuditFieldExtension(AuditFieldExtension $extension)
        {
            $this->auditFieldExtension = $extension;
        }

        public function up(Schema $schema, QueryBag $queries)
        {
            $this->auditFieldExtension->addType($schema, $doctrineType = 'datetimetz', $auditType = 'datetimetz');
        }
    }
```

* to see auditable option in entity configuration, make sure your field type is in allowed types here: DataAuditBundle/Resources/config/oro/entity_config.yml
* to make sure your column is correctly shown in grids (segments, reports...) it might be necessary to
create new column options guesser with tag: "oro_datagrid.column_options_guesser" and set frontend_type property
