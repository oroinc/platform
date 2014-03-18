OroSegmentBundle
===============

The goal of OroSegmentBundle is to provide entities segmentation, that can be used for further processing.

##Table of Contents
 - [Overview](#overview)
 - [Frontend implementation](#frontend-implementation)
 - [Backend implementation](#backend-implementation)
 - [Usage examples](#usage-examples)

## Overview

**Segment** - is representation of some dataset. It based on entity and set of filters.

So first of all segment is filtered data of the given entity type.
There are two types of segments:

 1. **Static** (is also called "On demand")
 2. **Dynamic**

The difference is that the dynamic segment displays real-time data, and static segment has a set of snapshots.
 It filters data in the same way as dynamic one and stores state into service table(`oro_segment_snapshot`).
 So even if data in real time is no longer correspond filtering criteria it will still exist in dataset of the static segment.
 So basically static segment is snapshot of filtered data in some point of time.

 Also both segment types could have table representation of data. It might be configured from segment management pages.

## Frontend implementation

Frontend part of segment management based on *condition builder* that comes from *OroQueryDesignerBundle*.
See [doc](../QueryDesignerBundle/Resources/doc/frontend/condition-builder.md) for further information. **Segmentation filter** based
on *AbstractFilter* from *OroFilterBundle* and provides ajax based autocomplete field in the meantime based on *JQuery.Select2* plugin.

## Backend implementation

### Entities

**Segment** entity is descendant of *AbstractQueryDesigner* model that comes from *OroQueryDesignerBundle*.
 Basically this entity contains entity name(based on), json encoded definition and service fields such as created/updated,
 owner etc. **SegmentType** is representation of possible segment types.
 Default types are loaded by data fixture migration mechanism. **SegmentSnapshot** is service entity.
 It contains snapshots data for **static** segments. It contains link on segment that it belongs to,
 *entityId* field that is link to entity of type that segment is based on, and date when this link was created.

### Query builders

As described before **static** and **dynamic** segments have different way how filtering tool should be applied.
There are two strategies how filtering by segment could be applied on query: *DynamicSegmentQueryBuilder* and *StaticSegmentQueryBuilder* correspondent.

### Datagrid

For table representation of segment used **OroDataGridBundle**. Configuration of grid is comes from segment definition that provided by *SegmentBundle\Grid\ConfigurationProvider*.
 It tries to retrieve segment identifier from grid name and pass loaded segment entity to *SegmentDatagridConfigurationBuilder*.
 Datagrid configuration is not process filtering in order to encapsulate filtering logic in the *SegmentFilter*.
 So for those purposes were created to proxy classes *DatagridSourceSegmentProxy* and *RestrictionSegmentProxy*.
 **DatagridSourceSegmentProxy** is overrides definition and provides only *segment filter* definition.
 So datagrid configuration builder receives entire definition except filters, only segment filter comes there.
*RestrictionSegmentProxy* used by *SegmentQueryConverter* in order to do not convert definition of the columns due
 to query builder need only one field in *SELECT* statement, it's entity identifier.

## Usage examples

Query could be retrieved using following code

```php
        if ($segment->getType()->getName() === SegmentType::TYPE_DYNAMIC) {
            $query = $this->dynamicSegmentQueryBuilder->build($segment);
        } else {
            $query = $this->staticSegmentQueryBuilder->build($segment);
        }
```

`$query` vatiable will contain instance of *\Doctrine\ORM\Query* after that it could be added in where statement of any doctrine query in following way:

```php
/** @var EntityManger $em */
$classMetadata = $em->getClassMetadata($segment->getEntity());
$identifiers   = $classMetadata->getIdentifier();

// SOME QUERY HERE
$qb = $em->createQueryBuilder()->select()
    ->from($segment->getEntity());

$alias = 'u';
// only not composite identifiers are supported
$identifier = sprintf('%s.%s', $alias, reset($identifiers));
$expr       = $qb->expr()->in($identifier, $query->getDQL());

$qb->where($expr);

$params = $query->getParameters();
/** @var Parameter $param */
foreach ($params as $param) {
    $qb->setParameter($param->getName(), $param->getValue(), $param->getType());
}
```
