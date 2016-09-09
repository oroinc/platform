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
 - [Import and export UI setup](#import-and-export-ui-setup)

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

```php
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

```yml
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

```yml
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

```yml
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

```json
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

```yml
services:
    orocrm_contact.importexport.strategy.group.add_or_replace:
        class: %orocrm_contact.importexport.strategy.group.class%
        parent: oro_importexport.strategy.configurable_add_or_replace
```

Import Processor
----------------

At this point after normalizers are registered, data converter is available and strategy is implemented import can be
already configured using DI configuration.

```yml
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

```yml
parameters:
    orocrm_contact.importexport.template_fixture.contact.class: OroCRM\Bundle\ContactBundle\ImportExport\TemplateFixture\ContactFixture

services:
    orocrm_contact.importexport.template_fixture.contact:
        class: %orocrm_contact.importexport.template_fixture.contact.class%
        tags:
            - { name: oro_importexport.template_fixture }

```

**Define fixture converter:**
```yml
    orocrm_contact.importexport.template_fixture.data_converter.contact:
        parent: oro_importexport.data_converter.template_fixture.configurable
```

**Define export processor:**
```yml
    orocrm_contact.importexport.processor.export_template:
        parent: oro_importexport.processor.export_abstract
        calls:
            - [setDataConverter, [@orocrm_contact.importexport.template_fixture.data_converter.contact]]
        tags:
            - { name: oro_importexport.processor, type: export_template, entity: %orocrm_contact.entity.class%, alias: orocrm_contact }

```

Import and export UI setup
----------------

In order to have the import (and download template) and export buttons displayed on your page, you have to include the
buttons generation template from the OroImportExportBundle. There are multiple options that can be used to configure the
display of these buttons and the pup-ups that can be set to appear in certain cases (export and download template).


**Options for import/export buttons configuration:**

General:
- refreshPageOnSuccess: set to true in order to refresh the page after successful import
- afterRefreshPageMessage: the message that will be displayed if previous option is set
- dataGridName: the id of the grid that will be used to refresh the data after an import operation (alternative to previus refresh options)
- options: options to pass to the import/export route
- entity_class: the full class name of the entity

Export:
- exportJob: the id of the export job you have defined
- exportProcessor: the alias id of the export processor or an array with the alias ids of the processors if they are more
than one
- exportLabel: the label that should be used for the export options pop-up (in case of multiple export processors)

Export template:
- exportTemplateJob: the id of the export template job you have defined
- exportTemplateProcessor: the alias id of the export template processor or an array with the alias ids of the processors
if they are more than one
- exportTemplatelabel: the label that should be used for the export template options pop-up (in case of multiple export
processors)

Import:
- importProcessor: the alias id of the import processor
- importLabel: the label used for import pop-up
- importJob: the id of the import job you have defined
- importValidateJob: the id of the import validation job you have defined


**Displaying import/export buttons:**

```twig
    {% include 'OroImportExportBundle:ImportExport:buttons.html.twig' with {
        entity_class: entity_class,
        exportJob: 'your_custom_entity_class_export_to_csv',
        exportProcessor: exportProcessor,
        importProcessor: 'oro.importexport.processor.import',
        exportTemplateProcessor: exportTemplateProcessor,
        exportTemplatelabel: 'oro.importexport.processor.export.template_popup.title'|trans,
        exportLabel: 'oro.importexport.processor.export.popup.title'|trans,
        dataGridName: gridName
    } %}
```

**Import pop-up:**

By using the default import configuration (like in the examples above) the user will have an import button displayed
on the configured page. By using this button, the user will see a pop-up where he must input a file for upload (and
validation) as well as selecting the import strategy. As seen in the import strategy section, the import process needs
a strategy, but it can also have multiple strategies defined.

Each strategy is used by an import processor, so the strategy will have to be passed to the import processor defined
for the current entity class. At the moment of generation the import pop-up, the framework will search for the defined
import processors for the given entity class and will display them in the selection for strategies.

**Exceptional use cases:**

The basic use case of import/export implies defining an import/export processor for an entity, which will be used when the user
selects the import/export operation from the application.

The are also cases when the export operations needs to extract data in multiple ways or from multiple entities and you
want to provide different export options to the user. In this situation you must define multiple export processors which
will handle the types of exports that you want to offer to the user.

If multiple export processors are defined for an entity, when the user wants to perform an export, the platform will
display a pop-up which will have a select with options corresponding to the export processors defined. Depending on what
option the user selects, the corresponding export processor will be used . You also have to define translation keys for
the ids of the processors. These translation keys will be used in the select options in the pop-up.

The same thing is applicable for the export of the templates used for import. You can have multiple export template
processors which will be displayed as options in a pop-up when user wants to download data template.

*Export processors definition:*
```yml
    oro.importexport.processor.export.some_type:
        parent: oro_importexport.processor.export_abstract
        calls:
            - [setDataConverter, [@oro.importexport.data_converter]]
        tags:
            - { name: oro_importexport.processor, type: export, entity: %oro.some_entity.class%, alias: oro_some_type }

    oro.importexport.processor.export.another_type:
        parent: oro_importexport.processor.export_abstract
        calls:
            - [setDataConverter, [@oro.importexport.data_converter]]
        tags:
            - { name: oro_importexport.processor, type: export, entity: %oro.some_entity.class%, alias: oro_another_type }
```

*Translation keys for selections in export pop-up:*
```yml
   #messages.en.yml
   oro.importexport.export.oro_some_type: Some export type
   oro.importexport.export.oro_another_type: Some other export type
```
In this case, you have to specify the processors that can be used as select options in the pop-up. On the configuration
of the import/export buttons you have to specify the processors as array, like in the example bellow(**exportProcessors**
and/or **exportTemplateProcessors**):

```twig
    {% include 'OroImportExportBundle:ImportExport:buttons.html.twig' with {
        ...
        exportProcessor: exportProcessors,
        exportTemplateProcessor: exportTemplateProcessors,
        ...
    } %}
```

**Change import/export pop-up dialog:**


*Import pop-up customization:*

If the user wants to have a different behaviour of the import pop-up he can extend the default **ImportType** from the
OroImportExportBundle and provide a different appearence for the form.

```php
<?php

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ImportExportBundle\Form\Type\ImportType;

class CustomImportTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ImportType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //TODO: add custom implementation for generating the form
    }
}
```

*Export pop-up customization:*

In case you want to display the export/export template options in a different way (other than the default select with
options) you have the possibility of extending the base types (**ExportType** and **ExportTemplateType**) from this
bundle which are used in displaying the form with options in the pop-up.

Example of displaying with choice(radio buttons):
```php
<?php

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ImportExportBundle\Form\Type\ExportType;

class CustomExportTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ExportType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //TODO: add custom implementation for generating the form
    }
}
```