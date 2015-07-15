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
