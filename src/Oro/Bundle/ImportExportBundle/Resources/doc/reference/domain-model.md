# Domain Model


## Table of Contents

 - [Job](#job)
    - [Job Executor](#job-executor)
    - [Job Result](#job-result)
 - [Context](#job)
    - [Context Interface](#context-interface)
    - [Step Execution Proxy Context](#step-execution-proxy-context)
    - [Context Registry](#context-registry)
 - [Reader](#reader)
    - [Reader Interface](#reader-interface)
    - [Csv File Reader](#csv-file-reader)
    - [Entity Reader](#entity-reader)
    - [Template Fixture Reader](#template-fixture-reader)
 - [Processor](#processor)
    - [Context Aware Processor](#context-aware-processor)
    - [Entity Name Aware Interface](#entity-name-aware-interface)
    - [Entity Name Aware Processor](#entity-name-aware-processor)
    - [Processor Interface](#processor-interface)
    - [Import Processor](#import-processor)
    - [Export Processor](#export-processor)
    - [Processor Registry](#processor-registry)
    - [Registry Delegate Processor](#registry-delegate-processor)
 - [Writer](#writer)
    - [Writer Interface](#writer-interface)
    - [Csv File Writer](#csv-file-writer)
    - [Entity Writer](#entity-writer)
    - [Doctrine Clear Writer](#doctrine-clear-writer)
 - [Converter](#converter)
    - [Abstract Table Data Converter](#abstract-table-data-converter)
    - [Configurable Table Data Converter](#configurable-table-data-converter)
    - [Data Converter Interface](#data-converter-interface)
    - [Default Data Converter](#default-data-converter)
    - [Query Builder Aware Interface](#query-builder-aware-interface)
    - [Relation Calculator](#relation-calculator)
    - [Relation Calculator Interface](#relation-calculator-interface)
    - [Template Fixture Relation Calculator](#template-fixture-relation-calculator)
 - [Strategy](#strategy)
    - [Strategy Interface](#strategy-interface)
    - [Import Strategy Helper](#import-strategy-helper)
    - [Configurable Add Or Replace Strategy](#configurable-add-or-replace-strategy)
 - [Serializer](#serializer)
    - [Serializer](#serializer-1)
    - [Dummy Encoder](#dummy-encoder)
    - [Normalizer](#normalizer)
        - [Abstract Context Mode Aware Normalizer](#abstract-context-mode-aware-normalizer)
        - [Collection Normalizer](#collection-normalizer)
        - [Configurable Entity Normalizer](#configurable-entity-normalizer)
        - [DateTime Normalizer](#datetime-normalizer)
        - [DateTime Normalizer](#datetime-normalizer)
        - [Denormalizer Interface](#denormalizer-interface)
        - [Normalizer Interface](#normalizer-interface)
 - [Template Fixture](#template-fixture)
    - [Template Fixture Interface](#template-fixture-interface)
    - [Template Fixture Registry](#template-fixture-registry)

## Job


### Job Executor

**Class:**

Oro\Bundle\ImportExportBundle\Job\JobExecutor

**Description:**

This class should be used to run import/export operations. It encapsulates all interactions with OroBatchBundle and is responsible for all job processing details in OroBatchBundle. It also supports the jobs transactional execution and handles exceptions and errors. As a result of the execution, the import/export operation returns the job result data.

**Methods:**

* **executeJob(jobType, jobName, configuration)** - executes a job and returns the job result data.

The *jobType* and *jobName* parameters of the executeJob method correspond to the OroBatchBundle jobs configuration.
The *configuration* parameter is a specific configuration of a job obtained by Context. Reader, Processor, and Writer have access to Context and can also acquire their configuration from it.

#### The jobType and jobName parameters in jobs configuration of OroBatchBundle

```
connector:
    name: oro_importexport
    jobs:
        entity_export_to_csv: # jobName
            title: "Entity Export to CSV"
            type: export # jobType
            steps:
                export:
                    title:     export
                    reader:    oro_importexport.reader.entity
                    processor: oro_importexport.processor.export_delegate
                    writer:    oro_importexport.writer.csv
```

### Job Result

**Class:**

Oro\Bundle\ImportExportBundle\Job\JobResult

**Description:**

The JobResult parameter encapsulates the results of the import/export execution. Upon import/export execution, the JobResult data is returned. It contains detailed information about the import/export execution status, such as an operation success status, an execution context, failure exceptions, and a job code.

## Context

### Context Interface

**Interface:**

Oro\Bundle\ImportExportBundle\Context\ContextInterface

**Description:**

Th context interface provides an interface for accessing different kinds of data and is shared during the import/export operation processing. 

The following data are available to access:
 * counters (how many records were read/written/added/deleted/replaced/updated);
 * errors (error messages and failure exception messages);
 * configuration (set up by a controller, used by any class that is involved in the import/export processing, and reads the context information);
 * options (the custom data that are reached by any context accessing objects).

### Step Execution Proxy Context

**Class:**

Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext

**Description:**

StepExecutionProxyContext is a wrapper of the Akeneo\Bundle\BatchBundle\Entity\StepExecution instance from AkeneoBatchBundle.

**Akeneo\Bundle\BatchBundle\Entity\StepExecution**

The instance of this class can store the data of step execution, such as a number of records that were read/written, errors, exceptions, warnings, and an execution context (Akeneo\Bundle\BatchBundle\Item\ExecutionContext) as well as the abstract data generated during the execution.

As the import/export domain has its own terms, ContextInterface expands the Akeneo\Bundle\BatchBundle\Entity\StepExecution interface and separates its clients from OroBatchBundle.

### Context Registry

**Class:**

Oro\Bundle\ImportExportBundle\Context\ContextRegistry

**Description:**

ContextRegistry is a storage which gets a specific instance of the context based on Akeneo\Bundle\BatchBundle\Entity\StepExecution and provides the interface to get a single instance context using Akeneo\Bundle\BatchBundle\Entity\StepExecution.

## Reader

### Reader Interface

**Interface:**

Oro\Bundle\ImportExportBundle\Reader\ReaderInterface

**Description:**

The reader interface is a class interface that is responsible for reading the data from some source. It is extended from the OroBatchBundle reader.

### CSV File Reader

**Class:**

Oro\Bundle\ImportExportBundle\Reader\CsvFileReader

**Description:**

The CSV file reader reads the data from a CSV file. The result of the operation is an array that represents a read line from the file. The keys of this array are taken from the first row or a custom header option.

**Configuration Options**

* **filePath** - path to a source file;
* **delimiter** - a CSV delimiter symbol (default ,);
* **enclosure** - a CSV enclosure symbol (default ");
* **escape** - a CSV escape symbol (default \);
* **firstLineIsHeader** - a flag that indicates that the first line of the CSV file is a header (default true);
* **header** - a custom header.

### Entity Reader

**Class:**

Oro\Bundle\ImportExportBundle\Reader\EntityReader

**Description:**

The entity reader reads entities using Doctrine. The Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator action is used to perform the reading which loads the data partially using internal batch and allows handling a large amount of data without memory lack errors. 

**Configuration Options**

* **entityName** - the name or class name of the entity;
* **queryBuilder** - an instance of custom Doctrine\ORM\QueryBuilder;
* **query** - an instance of custom Doctrine\ORM\Query.

One option is required, the options are mutually exclusive.

### Template Fixture Reader

**Class:**

Oro\Bundle\ImportExportBundle\Reader\TemplateFixtureReader

**Description:**

The fixture reader reads the import template data for the corresponding entity.

**Configuration Options:**

* **entityName** - the name or class name of the entity for which the fixture is loaded.

## Processor

### Context Aware Processor

**Interface:**

Oro\Bundle\ImportExportBundle\Processor\ContextAwareProcessor

**Description:**

The context aware processor is an interface used to work with a context inside processors. It aggregates ProcessorInterface and ContextAwareInterface.

**Methods:**

* **setImportExportContext(context)** - a context setter;

* **process(item)** - a process of the import/export operation. The item parameter comes from the reader, it can be an array read from a CSV file or one of the entity queries from Doctrine.

### Entity Name Aware Interface

**Interface:**

Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface

**Description:**

EntityNameAwareInterface is an interface used to work with an entity class inside processors.

**Methods:**

* **setEntityName(entityName)** - an entity name setter.

### Entity Name Aware Processor

**Interface:**

Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareProcessor

**Description:**

EntityNameAwareProcessor is an interface used to work with an entity class inside processors. It aggregates ProcessorInterface and EntityNameAwareInterface.

**Methods:**

* **setEntityName(entityName)** - an entity name setter;

* **process(item)** - a process of the import/export operation. The item parameter comes from the reader, it can be an array read from a CSV file or one of the entity queries from Doctrine.

### Processor Interface

**Interface:**

Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface

**Description:**

ProcessorInterface is an interface for a class that is processing the import/export operation. It is extended from the OroBatchBundle processor.

**Methods:**

* **process(item)** - a process of the import/export operation. The item parameter comes from the reader, it can be an array read from a CSV file or one of the entity queries from Doctrine.

### Import Processor

**Class:**

Oro\Bundle\ImportExportBundle\Processor\ImportProcessor

**Classes:**

* **Context** - manages the import configuration and its results;
* **Serializer** - deserializes the output of Data Converter to the entity object;
* **Data Converter** - converts the array of a reader format to the array of a serializer format;
* **Strategy** - performs a main logic of the import with a deserialized entity (Add/Update/Replace/Delete entities).

**Options:**

* **Class Name** - an imported entity class.

### Export Processor

**Class:**

Oro\Bundle\ImportExportBundle\Processor\ExportProcessor

**Classes:**

* **Context** - manages the export configuration and its results;
* **Serializer** - serializes the input entity to an array/scalar representation;
* **Data Converter** - converts a serialized array to a required format.

**Options:**

* **Class Name** - an exported entity class.

### Processor Registry

**Class:**

Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry

ProcessorRegistry provides a storage of all registered processors declared by the client bundles. A specific processor of an entity extends the basic one (Import Processor or Export Processor) and contains its own components (Serializer, Data Converter, Strategy). Such processor should be registered in DIC with the following tag:

```
services:
    orocrm_contact.importexport.processor.export:
        parent: oro_importexport.processor.export_abstract
        calls:
             - [setDataConverter, [@orocrm_contact.importexport.data_converter.contact]]
        tags:
            - { name: oro_importexport.processor, type: export, entity: %orocrm_contact.entity.class%, alias: orocrm_contact }
```

**Methods:**

* **registerProcessor(ProcessorInterface, type, entityName, alias)** - registers a processor using the input parameters;
* **unregisterProcessor(type, entityName, alias)** - unregisters the processor using the input parameters;
* **hasProcessor(type, alias)** - checks that the processor is registered;
* **getProcessor(type, alias)** - gets the registered processor;
* **getProcessorsByEntity(type, entityName)** - gets the registered processor by an entity. The import can have several processors for an entity, for example, one processor for the "Add and Replace" import behaviour and the other for the "Delete" import behaviour;
* **getProcessorAliasesByEntity(type, entityName)** - gets all processors aliases by a type and entity name;
* **getProcessorEntityName(type, alias)** - gets an entity name by the processor type and alias.

### Registry Delegate Processor

**Class:**

Oro\Bundle\ImportExportBundle\Processor\RegistryDelegateProcessor

**Description:**

RegistryDelegateProcessor uses the registry processor and configuration options from Context to delegate the processing.

**Classes:**

* **Processor Registry** - a processor storage;
* **Context Registry** - a context storage;
* **Step Execution** - a batch domain object representation of the step execution.

**Options:**

* **delegateType** - delegates a type (import, import_validation, export, export_template);
* **processorAlias** - an alias of a processor in Processor Registry.

## Writer

### Writer Interface

**Interface:**

Oro\Bundle\ImportExportBundle\Writer\WriterInterface

**Description:**

WriterInterface is an interface for a class that is responsible for recording the data to its destination place. It is triggered at the end of a query process chain, after Reader and Processor complete their operations. 

### Csv File Writer

**Interface:**

Oro\Bundle\ImportExportBundle\Writer\CsvFileWriter

**Description:**

This class records the data to a CSV file. It is used in the export job when entities are exported to the CSV file.

### Entity Writer

**Class:**

Oro\Bundle\ImportExportBundle\Writer\EntityWriter

**Description:**

EntityWriter is used in the import job. It persists and flushes the Doctrine entities enabling to perform the operations  with large amount of data without memory limit errors.

**Warning**

Clearing Doctrine can be dangerous and can lead to errors with detached entities in Doctrine's Unit of Work. To eliminate such errors, make sure that doctrine listeners do not set any values to the entities from the sources other than the Doctrine's repositories.

### Doctrine Clear Writer

**Class:**

Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter

**Description:**

DoctrineClearWriter clears Doctrine on each batch. It is used in the import validation job.

## Converter

### Abstract Table Data Converter

**Interface:**

Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter

**Description:**

AbstractTableDataConverter is an abstract class that is responsible for headers and conversion rules. It is extended and used in more complex use cases when you need to provide human-readable names of headers in the import/export files. The rules for AbstractTableDataConverter are configured to enable the corresponding data converting to the import/export formats. See Oro\Bundle\ContactBundle\ImportExport\Converter\ContactDataConverter as an example of the usage of this class.

**Methods:**

* **convertToExportFormat(exportedRecord, skipNullValues)** - converts exportedRecord to the format expected by its destination;
* **convertToImportFormat(importedRecord, skipNullValues)** - converts importedRecord to the format which is used to deserialize the entity from the array.

### Configurable Table Data Converter

**Interface:**

Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter

**Description:**

ConfigurableTableDataConverter is a class that is responsible for the data conversion.

**Methods:**

* **convertToExportFormat(exportedRecord, skipNullValues)** - converts exportedRecord to the format expected by its destination;
* **convertToImportFormat(importedRecord, skipNullValues)** - converts importedRecord to the format which is used to deserialize the entity from the array.

**Classes:**

* **FieldHelper** - a helper that works with the entity configuration;
* **RelationCalculator** - a class that calculates a relation collection size.

**Options:**

* **entityClass** - an entity class.

### Data Converter Interface

**Interface:**

Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface

**Description:**

DataConverterInterface is an interface for a class that is responsible for converting the data to the export/import format. It uses Processor that generally has its own Data Converter. The format of the input data depends on the serializer results.

**Methods:**

* **convertToExportFormat(exportedRecord, skipNullValues)** - converts exportedRecord to the format expected by its destination;
* **convertToImportFormat(importedRecord, skipNullValues)** - converts importedRecord to the format which is used to deserialize the entity from the array.

### Default Data Converter

**Class:**

Oro\Bundle\ImportExportBundle\Converter\DefaultDataConverter

**Description:**

DefaultDataConverter is applicable in simple cases of import/export. It can convert the data between two representations: one dimensional vs multi-dimensional arrays. It uses the ":" delimiter in keys to be converted between these two formats.

**Example of formats:**

```php
// Multi-dimensional
array(
    'name' => array(
        'first_name' => 'John',
        'last_name' => 'Doe',
    )
);
// One-dimensional
array(
    'name:first_name' => 'John',
    'name:last_name' => 'Doe',
);
```

**Methods:**

* **convertToExportFormat(exportedRecord, skipNullValues)** - converts the exportedRecord array to a one-dimensional array;
* **convertToImportFormat(importedRecord, skipNullValues)** - converts the importedRecord array to a multi-dimensional array.

### Query Builder Aware Interface

**Class:**

Oro\Bundle\ImportExportBundle\Converter\QueryBuilderAwareInterface

**Description:**

QueryBuilderAwareInterface is used to specify whether need to set query builder to the converter to perform additional adjustments.

**Methods:**

* **setQueryBuilder(queryBuilder)** - sets a query builder to the converter.

### Relation Calculator

**Class:**

Oro\Bundle\ImportExportBundle\Converter\RelationCalculator

**Description:**

RelationCalculator is a class used to count the collections and countable items.

**Methods:**

* **getMaxRelatedEntities(entityName, fieldName)** - counts the entities in relations.

**Classes:**

* **ManagerRegistry** - contracts covering object managers for a Doctrine persistence layer ManagerRegistry class to implement.
* **FieldHelper** - a helper that works with the entity configuration.

### Relation Calculator Interface

**Class:**

Oro\Bundle\ImportExportBundle\Converter\RelationCalculatorInterface

**Description:**

RelationCalculatorInterface is an interface used to count the collections and countable items.

**Methods:**

* **getMaxRelatedEntities(entityName, fieldName)** - counts the entities in relations.

### Template Fixture Relation Calculator

**Class:**

Oro\Bundle\ImportExportBundle\Converter\TemplateFixtureRelationCalculator

**Description:**

TemplateFixtureRelationCalculator is a class used to count the collections and countable items inside the import templates.

**Methods:**

* **getMaxRelatedEntities(entityName, fieldName)** - counts the entities in relations.

**Classes:**

* **TemplateManager** - fixture storage;
* **FieldHelper** - a helper that works with the entity configuration.

## Strategy

### Strategy Interface

**Interface:**

Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface

**Description:**

StrategyInterface is an interface for a class that is responsible for performing the import logic operations with the entities that were read and deserialized, for example, adding all read entities as new ones or updating the existing ones.

**Methods:**

* **process(entity)** - processes the entity with a specific logic.

### Import Strategy Helper

**Class:**

Oro\Bundle\ImportExportBundle\Strategy\ImportStrategyHelper

**Description:**

A helper class that is used by a specific strategy to perform some generic operations for the imported records.

**Methods:**

* **importEntity(basicEntity, importedEntity, excludedProperties)** - imports values of basicEntity to importedEntity using the Doctrine metadata;
* **validateEntity(entity)** - gets a list of validation errors;
* **addValidationErrors(validationErrors, ContextInterface, errorPrefix)** - adds validation errors to Context.


### Configurable Add Or Replace Strategy

**Class:**

Oro\Bundle\ImportExportBundle\Strategy\ConfigurableAddOrReplaceStrategy

**Description:**
The default strategy is used for the import. It updates the existing entities or adds new ones.

**Methods:**

* **process(entity)** - processes the entity with a specific logic.

**Classes:**

* **ContextInterface** - an execution context;
* **ImportStrategyHelper** - a strategy helper for generic import operations;
* **FieldHelper** - a helper that works with the entity configuration.

**Options:**

* **entityName** - an entity class.

## Serializer

### Dummy Encoder

**Class:**

Oro\Bundle\ImportExportBundle\Serializer\Encoder\DummyEncoder

**Description:**

This encoder is used by the import/export processor, no encoding/decoding is required, all work is done by normalizers.

### Serializer

**Class:**

Oro\Bundle\ImportExportBundle\Serializer\Serializer

**Description:**

Serializer is a class extended from a standard Symfony's serializer and used instead of it to perform serialization/deserialization. It has its own normalizers/denormalizers that are added using the following tags in the DI configuration:

```
services:
    oro_user.importexport.user_normalizer:
        class: %oro_user.importexport.user_normalizer.class%
        tags:
            - { name: oro_importexport.normalizer }
```

Each entity that you want to export/import should be supported by the import/export serializer. It means that you should add normalizers/denormalizers that are responsible for converting your entity to the array/scalar representation (normalization during serialization), and vice versa, converting the array to the entity object representation (denormalization during deserialization).

### Normalizer

It is a namespace for normalizers.

### Abstract Context Mode Aware Normalizer

**Class:**

Oro\Bundle\ImportExportBundle\Serializer\Normalizer\AbstractContextModeAwareNormalizer

**Description:**

AbstractContextModeAwareNormalizer ia an abstract normalizer that manages the available normalizers and default modes.

**Methods:**

* **normalize(object, format, context)** - a method used to convert objects to arrays;
* **denormalize(data, class, format, context)** - a method used to convert arrays to the `class` instance.

### Collection Normalizer

**Class:**

Oro\Bundle\ImportExportBundle\Serializer\Normalizer\CollectionNormalizer

**Description:**

Collection normalizer.

**Methods:**

* **normalize(object, format, context)** - a method used to convert objects to arrays;
* **denormalize(data, class, format, context)** - a method used to convert arrays to the `class` instance;
* **supportsNormalization(data, format, context)** - a method used to check a normalization support;
* **supportsDenormalization(data, format, context)** - a method used to check a denormalization support.

### Configurable Entity Normalizer

**Class:**

Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer

**Description:**

Entity normalizer manages the entity normalization and denormalization and resolves the entity DateTime class or relation.

**Methods:**

* **normalize(object, format, context)** - a method used to convert objects to arrays;
* **denormalize(data, class, format, context)** - a method used to convert arrays to the `class` instance;
* **supportsNormalization(data, format, context)** - a method used to check a normalization support;
* **supportsDenormalization(data, format, context)** - a method used to check a denormalization support;
* **setSerializer(serializer)** - a serializer setter from SerializerAwareInterface.

**Classes:**

* **FieldHelper** - a helper that works with the entity configuration.

### DateTime Normalizer

**Class:**

Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DateTimeNormalizer

**Description:**

DateTimeNormalizer is a normalizer for the DateTime objects.

**Methods:**

* **normalize(object, format, context)** - a method used to convert objects to arrays;
* **denormalize(data, class, format, context)** - a method used to convert arrays to the `class` instance;
* **supportsNormalization(data, format, context)** - a method used to check a normalization support;
* **supportsDenormalization(data, format, context)** - a method used to check a denormalization support.

### Denormalizer Interface

**Class:**

Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface

**Description:**

DenormalizerInterface extends `Symfony\Component\Serializer\Normalizer\DenormalizerInterface` and is used to pass the context to the `supportsDenormalization` method, providing more flexibility if more than one normalizer is used.

**Methods:**

* **supportsDenormalization(data, format, context)** - a method used to check a denormalization support.

### Normalizer Interface

**Class:**

Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface

**Description:**

NormalizerInterface extends `Symfony\Component\Serializer\Normalizer\NormalizerInterface` and is used to pass the context to the `supportsDenormalization` method, providing more flexibility if more than one normalizer is used.

**Methods:**

* **supportsNormalization(data, format, context)** - a method used to check a normalization support.

## TemplateFixture

Classes for the import template functionality.

### Template Fixture Interface

**Class:**

Oro\Bundle\ImportExportBundle\Serializer\Normalizer\TemplateFixtureInterface

**Description:**

TemplateFixtureInterface is an interface for the import fixtures.

**Methods:**

* **getData()** - returns the fixture data.

### Template Fixture Registry

**Class:**

Oro\Bundle\ImportExportBundle\Serializer\Normalizer\TemplateManager

**Description:**

Template for a fixtures registry.

**Methods:**

* **addEntityRepository(fixture)**  - adds a repository to a registry;
* **hasEntityFixture(entityClass)** - checks whether the fixture exists for given `entityClass`;
* **getEntityFixture(entityClass)** - returns the fixture for given `entityClass`.
