Datasources
===========

# Table of Contents

- [Overview](#overview)
- [Supported Types](#supported-types)
- [Custom Types](#custom-types)
- [ACL](#acl)

## Overview

OroPlatform gives you a wide variety of ways to prepare and supply data to a datagrid by encapsulating all data access in a *datasource* object. Datagrids can be configured to retrieve data from a PHP array, Doctrine ORM, a search engine or any other source by using datasources that implement `DatasourceInterface`.

## Supported Types

 - [ORM](datasources/orm.md)
 - [Array](datasources/array.md)
 - [Search](../../../../SearchBundle/Resources/doc/configuration.md#datagrid-configuration)


## Custom Types

To implement your own datasource type:

 - Create a class that implements `DatasourceInterface`
 - Register your type as a tagged service - `{ name: oro_datagrid.datasource, type: YOUR_CUSTOM_TYPE_NAME }`

```php
<?php

namespace Acme\Bundle\AcmeBundle\Datagrid\Datasource;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

class CustomDatasource implements DatasourceInterface
{
    const TYPE = 'acme_custom';

    /**
     * @var array
     */
    protected $arraySource = [];

    /** {@inheritdoc} */
    public function process(DatagridInterface $grid, array $config)
    {
        $grid->setDatasource(clone $this);
    }

    /** {@inheritdoc} */
    public function getResults()
    {
        $rows = [];
        foreach ($this->arraySource as $result) {
            $rows[] = new ResultRecord($result);
        }

        return $rows;
    }

    /**
     * @return array
     */
    public function getArraySource()
    {
        return $this->arraySource;
    }

    /**
     * @param array $source
     */
    public function setArraySource(array $source)
    {
        $this->arraySource = $source;
    }
}
```

Add the service definition to `services.yml`:

```yaml
acme_bundle.datagrid.datasource.array:
    class: 'Acme\Bundle\AcmeBundle\Datagrid\Datasource\CustomDatasource'
    tags:
        - { name: oro_datagrid.datasource, type: acme_custom }
```

Now that you've created your custom datasource type, you can use it in any datagrid. In the configuration of the datagrid tell OroPlatform to use this datasource via the `type` parameter under the `source` node:

```yaml
datagrids:
    DATAGRID_NAME_HERE:
        source:
            type: acme_custom
```

## ACL

You can protect a datasource with ACL by adding the `acl_resource` parameter under the `source` node in the datagrid configuration:

```yaml
datagrids:
    DATAGRID_NAME_HERE:
        source:
            acl_resource: SOME_ACL_IF_NEEDED
```
