Testing Exports
========

Table of Contents
-----------------
 - [Summary](#summary)
 - [Usage](#usage)
 - [Working example](#working-example)

Summary
-------
After preparing export, you may want to check, if export works as expected.
Instead of writing full functional test, we prepared special abstract test, 
that allows you to test exports fast and easy.

Usage
-----
### Create test

To test export simply create your own functional test and extends

```php
Oro\Bundle\ImportExportBundle\Tests\Functional\Export\AbstractExportTest;
```

For example:
```php
use Oro\Bundle\ImportExportBundle\Tests\Functional\Export\AbstractExportTest;

/**
 * @dbIsolationPerTest
 */
class CustomerExportTest extends AbstractExportTest
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
While preparing export you had to choose processor alias for you export jobs.
All you need to do is copy it from *importexport.yml*.

```php
    protected function getProcessorAlias()
    {
        return 'oro_customer_customer';
    }

```

### getEntityName()
You need to provide entity class name that is exported in a job.

```php
    protected function getEntityName()
    {
        return '\Oro\Bundle\CustomerBundle\Entity\Customer';
    }
```

### getContains()
Provide strings, that you expect to find in your exported csv file

```php
    protected function getContains()
    {
        return [
            'Id',
            'Name',
            'Parent',
            'Group Name',
        ];
    }
```

### getNotContains()
Provide strings, that must not appear in exported csv file
```php
    protected function getNotContains()
    {
        return [
            'Addresses'
        ];
    }
```

### getExpectedNumberOfLines()
Provide number of lines expected in the csv file. 
Remember not to count header.

```php
    protected function getExpectedNumberOfLines()
    {
        return 16;
    }
```

Working example
---------------
If you want to see working example, please see: 
*\Oro\Bundle\CustomerBundle\Tests\Functional\ImportExport\CustomerExportTest* 
