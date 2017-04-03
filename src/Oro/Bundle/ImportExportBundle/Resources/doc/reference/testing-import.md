Testing Import
========

Table of Contents
-----------------
 - [Summary](#summary)
 - [Usage](#usage)
 - [Working example](#working-example)

Summary
-------
After preparing import, you may want to check, if import works as expected.
Instead of writing full functional test, we prepared special abstract test, 
that allows you to test import fast and easy.

Usage
-----
### Create test

To test import simply create your own functional test and extend

```php
Oro\Bundle\ImportExportBundle\Tests\Functional\Import\AbstractImportTest;
```

For example:
```php
use Oro\Bundle\ImportExportBundle\Tests\Functional\Import\AbstractImportTest;

/**
 * @dbIsolationPerTest
 */
class CustomerUserImportTest extends AbstractImportTest
```

It will force you to prepare few methods required for test.

### Loading fixtures

If you wan to load some fixtures data, simply extend **setUp** method,
and call **loadFixtures()** method.

*NOTICE* Remember to always call **parent::setUp()**.

```php
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(['\Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers']);
    }
```

### getProcessorAlias()
While preparing import you had to choose processor alias for you import jobs.
All you need to do is copy it from *importexport.yml*.

```php
    protected function getProcessorAlias()
    {
        return 'oro_customer_customer';
    }

```

### getEntityName()
You need to provide entity class name that is imported.
It's important because abstract test creates repository to retrieve and compare entities.

```php
    protected function getEntityName()
    {
        return '\Oro\Bundle\CustomerBundle\Entity\Customer';
    }
```

### getFileName()
Provide csv filename to be imported.

```php
    protected function getFileName()
    {
        return dirname(__FILE__).'/data/import_template.csv';
    }
```

### getEntityArray()
Returns array used to compare entities that are in database after import.
It has such structure:

```php
    protected function getEntityArray()
    {
       return [
         ['id' => 1, 'name' => 'testname'],
         ['id' => 2, 'name' => 'testname2']
       ];
    }
```

this test compares all records from database ordered by ID ASC.
In this example it will compare first two records from database.
It also compares count of retrieved records compared to array count.

Working example
---------------
If you want to see working example, please see: 
*Oro\Bundle\CustomerBundle\Tests\Functional\Import\CustomerUserImportTest* 
