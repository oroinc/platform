How to add new auditable types
==============================

* first you need to register new type in your bundle's boot method

    ```php
    <?php

    use Oro\Bundle\DataAuditBundle\Entity\AuditField;

    class MyBundle extends Bundle
    {
        public function boot()
        {
            AuditField::addType($doctrineType = 'datetimetz', $auditType = 'datetimetz');
        }
    }
```

* then you have to create migration with columns (if they doesn't exists already)
    * columns needs to be named in format
        * sprintf('new_%s', $auditType)
        * sprintf('old_%s', $auditType)

    ```php
    <?php

    use Oro\Bundle\MigrationBundle\Migration\Migration;
    use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

    class MyMigration implements Migration
    {
        public function up(Schema $schema, QueryBag $queries)
        {
            $auditFieldTable = $schema->getTable('oro_audit_field');

            $auditFieldTable->addColumn('old_datetimetz', 'datetimetz', [
                'oro_options' => [
                    'extend' => ['owner' => ExtendScope::OWNER_SYSTEM]
                ],
                'notnull' => false
            ]);
            $auditFieldTable->addColumn('new_datetimetz', 'datetimetz', [
                'oro_options' => [
                    'extend' => ['owner' => ExtendScope::OWNER_SYSTEM]
                ],
                'notnull' => false
            ]);
        }
    }

    ```
