# Getting Started

### Table of Contents

- Getting Started
	- [What is Entity Merge](#what-is-entity-merge "What is Entity Merge")
	- [Main Entities](#main-entities)
	- [How it works](#how-it-works)
- [Classes Diagram](./classes-diagram.md)
- [Configuration](./merge-configuration.md)
	- [Entity configuration](./merge-configuration.md#entity-configuration)
	- [Mass action configuration](./merge-configuration.md#mass-action-configuration)
	- [Other configurations](./merge-configuration.md#other-configurations)


## What is Entity Merge ##

Entity merge is a complex solution that allows user to merge different entities into one. Usually entities merge is
used to remove copies of entity. Entity merge bundle provides functionality to select multiple entities from grid and
merging them in wizard.

## Main Entities ##

Entity merge consists of several related entities.

- **FieldMetadata** - metadata information of field merging.
- **EntityMetadata** - represents the list of metadata fields and entity merge metadata.
- **FieldData** - contains entity that was selected as source value and merge strategy mode (replace/unite).
- **EntityData** - contains master record that will be result of merge and the list of fields data.
- **Strategy** - strategy for entity field merge. For example: unite or replace. Default strategies:
   - **UniteStrategy** - merge field values into Master Entity. It works only with fields, which represented by the list of entities
   - **ReplaceStrategy** - replace master entity field value with selected one.
- **Step** - one of merge steps. By defaul there are three steps: **ValidateStep**, **MergeFieldsStep** and **RemoveEntitiesStep**
- **Accessor** - provide access (get value/set value) for merge fields

## How it works ##

1. Entity has [merge configuration](./merge-configuration.md) and grid with "Merge" mass action
2. User selects records to merge on the grid and click "Merge" mass action
3. User redirected to merge entities page. This page contains wizard for entities merge. Wizard allow user set up merge process by choosing merge strategy and prefer values. So user can merge several entities in one.

There one important setting in wizard called "Master Record". This setting allow user to define which entity will contain result of merge.
Other entities will be removed from the database. All doctrine references to removed entities will be replaced with master record.
