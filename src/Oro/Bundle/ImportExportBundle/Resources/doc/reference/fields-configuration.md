Fields Configuration
====================

Table of Contents
-----------------
 - [Header](#header)
 - [Order](#order)
 - [Identity](#identity)
 - [Excluded](#excluded)
 - [Full](#full)


Header
------

This option used to configure custom column header, field label is used by default.

```php
    use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

    /**
     * @var string
     *
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "header"="Custom field"
     *          }
     *      }
     * )
     */
    protected $field;

```


Order
-----

This option used to configure custom column order.

```php
    use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

    /**
     * @var string
     *
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=100
     *          }
     *      }
     * )
     */
    protected $field;

```


Identity
--------

Fields with this option are used to identify (search) entity. It is possible to use multiple identity fields for one
entity.

```php
    use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

    /**
     * @var string
     *
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $field;

```


Excluded
--------

Fields with this option are not exported.

```php
    use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

    /**
     * @var string
     *
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $field;

```


Full
----

All fields of the related entity are exported. Fields with [Excluded](#excluded) option are skipped.

```php
    use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

    /**
     * @var string
     *
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "full"=true
     *          }
     *      }
     * )
     */
    protected $field;

```