How To Use
==========

Table of Contents
-----------------
 - [Adding Normalizers](#adding-normalizers)
 - [Adding Data Converter](#adding-data-converter)
 - [Export Processor](#export-processor)
 - [Import Strategy](#import-strategy)
 - [Import Processor](#import-processor)
 - [Fixture Services](#fixture-services)

Adding Normalizers
------------------

Serializer is involved both in import and export operations. It's extended from standard Symfony's `Serializer` and uses
extended `DenormalizerInterface` and `NormalizerInterface` interfaces (with context support for `supportsNormalization`
and `supportsDenormalization`). Responsibility of serializer is converting entities to plain/array representation
(serialization) and vice versa converting plain/array representation to entity objects (deserialization).

Serializer uses normalizers to perform converting of objects. So you need to provide normalizers for entities that
will be imported/exported.

Requirement to normalizer is to implement interfaces:
* **Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface** - used in export
* **Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface** - used in import

Generally you should implement both interfaces if you need to add both import and export for entity.

**Example of simple normalizer**

```
<?php

namespace OroCRM\Bundle\ContactBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;

use OroCRM\Bundle\ContactBundle\Entity\Group;

class GroupNormalizer extends ConfigurableEntityNormalizer
{
    public function normalize($object, $format = null, array $context = array())
    {
        $result = parent::normalize($object, $format, $context);

        // call some service to modify $result
    }

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        // call some service to modify $data

        return parent::denormalize($data, $class, $format, $context);
    }

    public function supportsNormalization($data, $format = null, array $context = array())
    {
        return $data instanceof Group;
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = array())
    {
        return is_array($data) && $type == 'OroCRM\Bundle\ContactBundle\Entity\Group';
    }
}

```

Serializer of OroImportExportBundle should be aware of it's normalizer. To make it possible use appropriate tag in DI
configuration:

**Example of normalizer service configuration**

```
parameters:
    orocrm_contact.importexport.normalizer.group.class: OroCRM\Bundle\ContactBundle\ImportExport\Serializer\Normalizer\GroupNormalizer
services:
    orocrm_contact.importexport.normalizer.group:
        class: %orocrm_contact.importexport.normalizer.group.class%
        parent: oro_importexport.serializer.configurable_entity_normalizer
        tags:
            - { name: oro_importexport.normalizer }
```


Adding Data Converter
---------------------

Data converter is responsible for converting header of import/export file. Assume that your entity has some properties
that should be exposed in export file. You can use default Data Converter
`Oro\Bundle\ImportExportBundle\Converter\DefaultDataConverter` but if there is a need to have custom labels instead of
names of properties in export/import files, you can extend `Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter`

**Example Of Custom Data Converter**

```php
<?php

namespace OroCRM\Bundle\ContactBundle\ImportExport\Converter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use OroCRM\Bundle\ContactBundle\ImportExport\Provider\ContactHeaderProvider;

class GroupDataConverter extends AbstractTableDataConverter
{
    /**
     * {@inheritDoc}
     */
    protected function getHeaderConversionRules()
    {
        return array('ID' => 'id', 'Label' => 'label');
    }

    /**
     * {@inheritDoc}
     */
    protected function getBackendHeader()
    {
        return array('id', 'label');
    }
}

```

**Service**

```
services:
    orocrm_contact.importexport.data_converter.group:
        parent: oro_importexport.data_converter.configurable
```

Look at more complex example of DataConverter in OroCRMContactBundle
`OroCRM\Bundle\MagentoBundle\ImportExport\Converter\OrderAddressDataConverter`.


Export Processor
----------------

At this point after normalizers are registered and data converter is available export can be already configured using
DI configuration.

```
services:
    orocrm_contact.importexport.processor.export_group:
        parent: oro_importexport.processor.export_abstract
        calls:
             - [setDataConverter, [@orocrm_contact.importexport.data_converter.group]]
        tags:
            - { name: oro_importexport.processor, type: export, entity: %orocrm_contact.group.entity.class%, alias: orocrm_contact_group }
```

There is a controller in OroImportExportBundle that can be utilized to request export CSV file. See controller action
OroImportExportBundle:ImportExport:instantExport (route **oro_importexport_export_instant**).

Now if you'll send a request to URL **/export/instant/orocrm_contact_group** you will receive a response with URL
of result exported file and some additional information:

```
{
    "success":true,
    "url":"/export/download/orocrm_contact_group_2013_10_03_13_44_53_524d4aa53ffb9.csv",
    "readsCount":3,
    "errorsCount":0
}
```

Import Strategy
---------------

Strategy is class a that responsible for processing import logic. For example import could add new records or
it can update only existed ones.


**Example of Import Strategy**

```php
<?php

namespace OroCRM\Bundle\ContactBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

class ContactAddOrReplaceStrategy extends ConfigurableAddOrReplaceStrategy
{
    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $entity = parent::process($entity);

        if ($entity) {
            $this
                ->updateAddresses($entity);
        }

        return $entity;
    }

    // other methods
```

**Service**

```
services:
    orocrm_contact.importexport.strategy.group.add_or_replace:
        class: %orocrm_contact.importexport.strategy.group.class%
        parent: oro_importexport.strategy.configurable_add_or_replace
```

Import Processor
----------------

At this point after normalizers are registered, data converter is available and strategy is implemented import can be
already configured using DI configuration.

```
services:
    # Import processor
    orocrm_contact.importexport.processor.import_group:
        parent: oro_importexport.processor.import_abstract
        calls:
             - [setDataConverter, [@orocrm_contact.importexport.data_converter.group]]
             - [setStrategy, [@orocrm_contact.importexport.strategy.group.add_or_replace]]
        tags:
            - { name: oro_importexport.processor, type: import, entity: %orocrm_contact.group.entity.class%, alias: orocrm_contact.add_or_replace_group }
            - { name: oro_importexport.processor, type: import_validation, entity: %orocrm_contact.entity.class%, alias: orocrm_contact.add_or_replace_group }
```

Note that for import should be a processor for import validation as in example above.

Import can be done in three steps.

At the first step user fill out the form with source file that he want to import and submit it. See controller action
OroImportExportBundle:ImportExport:importForm (route "oro_importexport_import_form"), this action require parameter
"entity" which is a class name of entity that will be imported.

At the second step import validation is triggered. See controller action OroImportExportBundle:ImportExport:importValidate
(route "oro_importexport_import_validate"). As a result a user will see all actions that will be performed by import and
errors that were occurred. Records with errors can't be imported but errors not blocks valid records.

At the last step import is processed. See controller action OroImportExportBundle:ImportExport:importProcess
(route "oro_importexport_import_process").

Fixture Services
----------------

Fixtures implementation based on default import/export process.

**Create class:**

```php
<?php

namespace OroCRM\Bundle\ContactBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;

use OroCRM\Bundle\ContactBundle\Entity\Contact;

class ContactFixture implements TemplateFixtureInterface
{
    /**
     * @var TemplateFixtureInterface
     */
    protected $userFixture;

    /**
     * @param TemplateFixtureInterface $userFixture
     */
    public function __construct(TemplateFixtureInterface $userFixture)
    {
        $this->userFixture = $userFixture;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $contact = new Contact();
        $contact
            ->setFirstName('Jerry')
            ->setLastName('Coleman');

        return new \ArrayIterator(array($contact));
    }
}

```

**Define a service:**

```
parameters:
    orocrm_contact.importexport.template_fixture.contact.class: OroCRM\Bundle\ContactBundle\ImportExport\TemplateFixture\ContactFixture

services:
    orocrm_contact.importexport.template_fixture.contact:
        class: %orocrm_contact.importexport.template_fixture.contact.class%
        tags:
            - { name: oro_importexport.template_fixture }

```

**Define fixture converter:**
```
    orocrm_contact.importexport.template_fixture.data_converter.contact:
        parent: oro_importexport.data_converter.template_fixture.configurable
```

**Define export processor:**
```
    orocrm_contact.importexport.processor.export_template:
        parent: oro_importexport.processor.export_abstract
        calls:
            - [setDataConverter, [@orocrm_contact.importexport.template_fixture.data_converter.contact]]
        tags:
            - { name: oro_importexport.processor, type: export_template, entity: %orocrm_contact.entity.class%, alias: orocrm_contact }

```
