# How To Use

## Table of Contents

 - [Adding Normalizers](#adding-normalizers)
 - [Adding Data Converter](#adding-data-converter)
 - [Export Processor](#export-processor)
 - [Import Strategy](#import-strategy)
 - [Import Processor](#import-processor)
 - [Fixture Services](#fixture-services)
 - [Import and export UI setup](#import-and-export-ui-setup)
 - [Storage configuration](#storage-configuration)

## Adding Normalizers

The serializer is involved both in the import and export operations. It is extended from the standard Symfony's `Serializer` and uses the extended `DenormalizerInterface` and `NormalizerInterface` interfaces (with a context support for `supportsNormalization` and `supportsDenormalization`). The serializer's responsibility is to convert the entities to a plain array representation (serialization) and vice versa converting the plain array representation to entity objects (deserialization).

The serializer uses normalizers for the entities that will be imported/exported to perform converting of objects. 

The following requirements should be met for the normalizers to implement interfaces:
* **Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface** - used in export.
* **Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface** - used in import.

Generally, you should implement both interfaces if you need to add both import and export for the entity.

**Example of a Simple Normalizer**

```php
<?php

namespace Oro\Bundle\ContactBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;

use Oro\Bundle\ContactBundle\Entity\Group;

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
        return is_array($data) && $type == 'Oro\Bundle\ContactBundle\Entity\Group';
    }
}

```

The serializer of OroImportExportBundle should be aware of its normalizer. To make it possible, use the appropriate tag in the DI configuration:

**Example of Normalizer Service Configuration**

```yml
parameters:
    orocrm_contact.importexport.normalizer.group.class: Oro\Bundle\ContactBundle\ImportExport\Serializer\Normalizer\GroupNormalizer
services:
    orocrm_contact.importexport.normalizer.group:
        class: %orocrm_contact.importexport.normalizer.group.class%
        parent: oro_importexport.serializer.configurable_entity_normalizer
        tags:
            - { name: oro_importexport.normalizer }
```


## Adding Data Converter

The data converter is responsible for converting the header of the import/export file. Assuming that an entity has some properties to be exposed in the export file. You can use the default `Oro\Bundle\ImportExportBundle\Converter\DefaultDataConverter` Data Converter  however, if there is a necessity to have custom labels instead of the properties names in the export/import files, you can extend `Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter`.

**Example Of a Custom Data Converter**

```php
<?php

namespace Oro\Bundle\ContactBundle\ImportExport\Converter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use Oro\Bundle\ContactBundle\ImportExport\Provider\ContactHeaderProvider;

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
    oro_contact.importexport.data_converter.group:
        parent: oro_importexport.data_converter.configurable
```

Here, there is a more complex example of DataConverter in OroContactBundle
`Oro\Bundle\MagentoBundle\ImportExport\Converter\OrderAddressDataConverter`.

## Export Processor

Once the normalizers are registered and the data converter is available, you can configure the export settings using the DI configuration.

```yml
services:
    oro_contact.importexport.processor.export_group:
        parent: oro_importexport.processor.export_abstract
        calls:
             - [setDataConverter, [@orocrm_contact.importexport.data_converter.group]]
        tags:
            - { name: oro_importexport.processor, type: export, entity: %orocrm_contact.group.entity.class%, alias: orocrm_contact_group }
```

There is a controller in OroImportExportBundle that is used to request a CSV file export. See the controller action, defined in the OroImportExportBundle:ImportExport:instantExport method, route **oro_importexport_export_instant**.

Now, if you send a request to the **/export/instant/orocrm_contact_group** URL  you will receive a response with the URL of the exported file results and some additional information:

```json
{
    "success":true,
    "url":"/export/download/orocrm_contact_group_2013_10_03_13_44_53_524d4aa53ffb9.csv",
    "readsCount":3,
    "errorsCount":0
}
```

## Import Strategy

The strategy is a class that is responsible for the import logic processing, such as adding new records or updating the existing ones.

**Example of the Import Strategy**

```php
<?php

namespace Oro\Bundle\ContactBundle\ImportExport\Strategy;

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

Also, you can use [rows postponing](rows-postponing.md) in the strategy .

**Service**

```yml
services:
    oro_contact.importexport.strategy.group.add_or_replace:
        class: %orocrm_contact.importexport.strategy.group.class%
        parent: oro_importexport.strategy.configurable_add_or_replace
```

## Import Processor

Once the normalizers are registered, the data converter is available, and the strategy is implemented, you can configure the import using the following DI configuration.

```yml
services:
    # Import processor
    oro_contact.importexport.processor.import_group:
        parent: oro_importexport.processor.import_abstract
        calls:
             - [setDataConverter, [@orocrm_contact.importexport.data_converter.group]]
             - [setStrategy, [@orocrm_contact.importexport.strategy.group.add_or_replace]]
        tags:
            - { name: oro_importexport.processor, type: import, entity: %orocrm_contact.group.entity.class%, alias: orocrm_contact.add_or_replace_group }
            - { name: oro_importexport.processor, type: import_validation, entity: %orocrm_contact.entity.class%, alias: orocrm_contact.add_or_replace_group }
```

Note, that the import requires a processor for import validation as in the example above.

The import can be done in three steps.

At the first step, a user fills out the form (defined in the OroImportExportBundle:ImportExport:importForm, route "oro_importexport_import_form") in a source file that they want to import and submits it. This action requires the "entity" parameter which is a class name of the imported entity.

At the second step, the import validation action (defined in the OroImportExportBundle:ImportExport:importValidate method, route "oro_importexport_import_validate") is triggered. As a result, all the actions performed by import and all the errors occurred are visible to the user. The records with errors cannot be imported, though the errors do not block further processing of the valid records.

At the last step, the import action (defined in the OroImportExportBundle:ImportExport:importProcess method, route "oro_importexport_import_process") is processed. 

## Fixture Services

The fixture implementation is based on the default import/export process.

**Create class:**

```php
<?php

namespace Oro\Bundle\ContactBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;

use Oro\Bundle\ContactBundle\Entity\Contact;

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
    
    public function getEntityClass()
    {
        return Contact::class;
    }
    
    public function getEntity($key)
    {
        return new Contact();
    }
    
    public function fillEntityData($key, $entity)
    {}
}

```

**Define a service:**

```yml
parameters:
    oro_contact.importexport.template_fixture.contact.class: Oro\Bundle\ContactBundle\ImportExport\TemplateFixture\ContactFixture

services:
    oro_contact.importexport.template_fixture.contact:
        class: %orocrm_contact.importexport.template_fixture.contact.class%
        tags:
            - { name: oro_importexport.template_fixture }

```

**Define fixture converter:**
```yml
    oro_contact.importexport.template_fixture.data_converter.contact:
        parent: oro_importexport.data_converter.template_fixture.configurable
```

**Define export processor:**
```yml
    oro_contact.importexport.processor.export_template:
        parent: oro_importexport.processor.export_abstract
        calls:
            - [setDataConverter, [@orocrm_contact.importexport.template_fixture.data_converter.contact]]
        tags:
            - { name: oro_importexport.processor, type: export_template, entity: %orocrm_contact.entity.class%, alias: orocrm_contact }

```

## Import and export UI setup

In order to have the import (and download template) and export buttons displayed on your page, you have to include the buttons generation template from OroImportExportBundle. There are multiple options that can be used to configure the display of these buttons and the pop-ups that can be set to appear in certain cases (export and download template).

**Options for the import/export buttons configuration:**

General:
- refreshPageOnSuccess: set to true in order to refresh the page after the successful import.
- afterRefreshPageMessage: the message that is displayed if the previous option is set.
- datagridName: the ID of the grid that is used to refresh the data after the import operation is completed (alternative to the previous refresh option).
- options: options to pass to the import/export route.
- entity_class: a full class name of the entity.

Export:
- exportJob: the ID of the export job you have defined.
- exportProcessor: the alias ID of the export processor or an array with the alias IDs of the processors if they are more than one.
- exportLabel: the label that should be used for the export options pop-up (in case of multiple export processors).

Export template:
- exportTemplateJob: the ID of the export template job you have defined.
- exportTemplateProcessor: the alias ID of the export template processor or an array with the alias IDs of the processors if they are more than one.
- exportTemplateLabel: the label that should be used for the export template options pop-up (in case of multiple export processors).

Import:
- importProcessor: the alias ID of the import processor.
- importLabel: the label used for the import pop-up.
- importJob: the ID of the import job you have defined.
- importValidateJob: the ID of the import validation job you have defined.


**Display import/export buttons:**

```twig
    {% include 'OroImportExportBundle:ImportExport:buttons.html.twig' with {
        entity_class: entity_class,
        exportJob: 'your_custom_entity_class_export_to_csv',
        exportProcessor: exportProcessor,
        importProcessor: 'oro.importexport.processor.import',
        exportTemplateProcessor: exportTemplateProcessor,
        exportTemplateLabel: 'oro.importexport.processor.export.template_popup.title'|trans,
        exportLabel: 'oro.importexport.processor.export.popup.title'|trans,
        datagridName: gridName
    } %}
```


**Displaying import/export buttons for multiple entities:**

In order to display import/export buttons for several entities, you need to create configuration
providers for each entity with options, described in the beginning of the section:

```php
<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Translation\TranslatorInterface;

class ProductImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException
     */
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => Product::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_product_product',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_product_product_export_template',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_product_product.add_or_replace',
            ImportExportConfiguration::FIELD_DATA_GRID_NAME => 'products-grid',
            ImportExportConfiguration::FIELD_IMPORT_BUTTON_LABEL =>
                $this->translator->trans('oro.product.import.button.label'),
            ImportExportConfiguration::FIELD_IMPORT_VALIDATION_BUTTON_LABEL =>
                $this->translator->trans('oro.product.import_validation.button.label'),
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_BUTTON_LABEL =>
                $this->translator->trans('oro.product.export_template.button.label'),
            ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL =>
                $this->translator->trans('oro.product.export.button.label'),
            ImportExportConfiguration::FIELD_IMPORT_POPUP_TITLE =>
                $this->translator->trans('oro.product.import.popup.title'),
        ]);
    }
}
```

Provider's service should have a tag with the name `oro_importexport.configuration` and some alias.
The alias is used to group import/export buttons with different configurations on one page:

```yaml
oro_product.importexport.configuration_provider.product:
    class: 'Oro\Bundle\ProductBundle\ImportExport\Configuration\ProductImportExportConfigurationProvider'
    arguments:
        - '@translator'
    tags:
        - { name: oro_importexport.configuration, alias: oro_product_index }
```

To show all import/export buttons on a page, which are defined by configuration providers with an alias,
include following template:

```twig
{% include 'OroImportExportBundle:ImportExport:buttons_from_configuration.html.twig' with {
    'alias': 'oro_product_index'
} %}
```


**Import pop-up:**

By using the default import configuration (like in the examples above), a user has an import button displayed on the configured page. By clicking this button, a pop-up is displayed and the user needs to input a file for uploading (and validation) as well as selecting the import strategy. As described in the import strategy section, the import process requires
a strategy, but it can also have multiple strategies defined.

Each strategy is used by an import processor, so the strategy has to be passed to the import processor defined for the current entity class. While generating the import pop-up, the framework is searching for the defined import processors for the given entity class and displays them in the selection of strategies.

**Exceptional use cases:**

The basic use case of import/export implies defining an import/export processor for an entity which is used when the user selects the import/export operation from the application.

There are also cases when the export operation needs to extract the data in multiple ways or from multiple entities and you
want to provide different export options to the user. In this situation, you must define multiple export processors which can handle the types of exports that you want to offer to the user.

If multiple export processors are defined for an entity and the user wants to perform an export, the platform displays a pop-up with a possibility to select a required option corresponding to the defined export processors. Depending on the option selected, the corresponding export processor is used. You also have to define translation keys for
the IDs of the processors. These translation keys are used in the selected option in the pop-up.

The same thing is applicable for the export of the templates used for the import. You can have multiple export template processors which are displayed as options in a pop-up when the user wants to download a data template.

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

*Translation keys for selections in an export pop-up:*
```yml
   #messages.en.yml
   oro.importexport.export.oro_some_type: Some export type
   oro.importexport.export.oro_another_type: Some other export type
```
In this case, you have to specify the processors that can be used as selected options in the pop-up. On the import/export buttons configuration, specify the processors as array, like in the example bellow (**exportProcessors** and/or **exportTemplateProcessors**):

```twig
    {% include 'OroImportExportBundle:ImportExport:buttons.html.twig' with {
        ...
        exportProcessor: exportProcessors,
        exportTemplateProcessor: exportTemplateProcessors,
        ...
    } %}
```

**Change an import/export pop-up dialog:**


*Import a pop-up customization:*

To implement custom behaviour of the import pop-up, you can extend the default **ImportType** from OroImportExportBundle and implement a custom form appearance.

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

*Export a pop-up customization:*

To display the export/export template options in a different way (other than the default
options selection), you can extend the base types (**ExportType** and **ExportTemplateType**) from the ImportExport bundle. These types are used when displaying the form with options in the pop-up.

Example of displaying the form with choice (radio buttons):
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

## Storage configuration

OroImportExportBundle uses [KnpGaufretteBundle](https://github.com/KnpLabs/KnpGaufretteBundle) to provide a filesystem abstraction layer.

By default, it is configured to store files in the `var/import_export` directory of your project. You can change it in the `Resources/config/oro/app.yml` file. A user can reconfigure these settings. More information about the KnpGaufretteBundle configuration can be found in [documentation](https://github.com/KnpLabs/KnpGaufretteBundle/blob/master/README.markdown).
