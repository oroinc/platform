# Getting Started #

----------

### Table of Contents ###

----------
- Getting Started
	- [What is Entity Merge](#what-is-entity-merge "What is Entity Merge")
	- [Main Entities](#main-entities)
	- [How it works](#how-it-works)
- [Classes Diagram](./classes-diagram.md)
- [Configuration](./configuration.md)

----------

## What is Entity Merge ##

Entity merge is a complex solution that allows user to merge different entities into one. Usually entities merge is used to remove copies of entity. Entity merge bundle provides functionality to select multiple entities from grid and merging them in wizard.

## Main Entities ##

Entity merge consists of several related entities.

- **Field Meta Data** - contain Field Meta Data with all field options
- **Entity Meta Data** - contain list of Fields Meta Data and entity options
- **Field Data** - representation of field which need to be merge. Contains Field Meta Data.
- **Entity Data** - representation of entity which need to be merge. Contains Entity Meta Data, list of entities for merge, list of Field Data wizard and Master Entity

## How it works ##

When user will come to page with merge mass action he will see several check boxes and merge button. 
He can check several of the check boxes and press merge button. In this case he will be redirected to merge entities page. This page contains wizard for entities merge. Wizard allow user set up merge process by choosing merge strategy and prefer values. So user can merge several entities in one. 

There one important setting in wizard called "Master Record". This setting allow user to define which entity will contain result of merge.




