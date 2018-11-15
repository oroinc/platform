# Filters

 - [Overview](#overview)
 - [ComparisonFilter Filter](#comparisonfilter-filter)
 - [Existing Filters](#existing-filters)
 - [FilterInterface Interface](#filterinterface-interface)
 - [CollectionAwareFilterInterface Interface](#collectionawarefilterinterface-interface)
 - [MetadataAwareFilterInterface Interface](#metadataawarefilterinterface-interface)
 - [RequestAwareFilterInterface Interface](#requestawarefilterinterface-interface)
 - [SelfIdentifiableFilterInterface Interface](#selfidentifiablefilterinterface-interface)
 - [NamedValueFilterInterface Interface](#namedvaluefilterinterface-interface)
 - [StandaloneFilter Base Class](#standalonefilter-base-class)
 - [StandaloneFilterWithDefaultValue Base Class](#standalonefilterwithdefaultvalue-base-class)
 - [Criteria Class](#criteria-class)
 - [CriteriaConnector Class](#criteriaconnector-class)
 - [QueryExpressionVisitor Class](#queryexpressionvisitor-class)
 - [Query Expressions](#query-expressions)
 - [Creating New Filter](#creating-new-filter)
 - [Other Classes](#other-classes)

## Overview

This chapter provides information on the existing filters and illustrates how to create them.

Filters are used to limit a set of data or request additional information returned by the data API.

Filters for fields that have a database index are enabled automatically. Filters by all other fields should be
[enabled explicitly](./configuration.md#filters-configuration-section), if necessary.

## ComparisonFilter Filter

The [ComparisonFilter](../../Filter/ComparisonFilter.php) is the default filter used to filter data by a field value
using various comparison types.

All supported comparison types are listed in the following table:

| Comparison Type | Operator | Description |
|-----------------|----------|-------------|
| eq              | `=`  | For fields and not collection valued associations checks whether a field value is equal to a filter value. For collection valued associations checks whether a collection contains any of filter values. |
| neq             | `!=` | For fields and not collection valued associations checks whether a field value is not equal to a filter value. For collection valued associations checks whether a collection does not contain any of filter values. Records that have `null` as the field value or empty collection are not returned. To return such records the `neq_or_null` comparison type should be used. |
| lt              | `<`  | Checks whether a field value is less than a filter value. Supports numeric, date and time fields. |
| lte             | `<=` | Checks whether a field value is less than or equal to a filter value. Supports numeric, date and time fields. |
| gt              | `>`  | Checks whether a field value is greater than a filter value. Supports numeric, date and time fields. |
| gte             | `>=` | Checks whether a field value is greater than or equal to a filter value. Supports numeric, date and time fields. |
| exists          | `*`  | For fields and not collection valued associations checks whether a field value is not `null` (if a filter value is `true`) or a field value is `null` (if a filter value is `false`). For collection valued associations checks whether a collection is not empty (if a filter value is `true`) or a collection is empty (if a filter value is `false`). |
| neq_or_null     | `!*` | For fields and not collection valued associations checks whether a field value is not equal to a filter value or it is `null`. For collection valued associations checks whether a collection does not contain any of filter values or it is empty. |
| contains        | `~`  | For string fields checks whether a field value contains a filter value. The `LIKE '%value%'` comparison is used. For collection valued associations checks whether a collection contains all of filter values. |
| not_contains    | `!~` | For string fields checks whether a field value does not contain a filter value. The `NOT LIKE '%value%'` comparison is used. For collection valued associations checks whether a collection does not contain all of filter values. |
| starts_with     | `^`  | Checks whether a field value starts with a filter value. The `LIKE 'value%'` comparison is used. Supports only string fields. |
| not_starts_with | `!^` | Checks whether a field value does not start with a filter value. The `NOT LIKE 'value%'` comparison is used. Supports only string fields. |
| ends_with       | `$`  | Checks whether a field value ends with a filter value. The `LIKE '%value'` comparison is used. Supports only string fields. |
| not_ends_with   | `!$` | Checks whether a field value does not end with a filter value. The `NOT LIKE '%value'` comparison is used. Supports only string fields. |

## Existing Filters

A list of filters that are configured automatically according to the data type of a field:

| Data Type / Filter Type | Operators enabled by default               |
|-------------------------|--------------------------------------------|
| string                  | `=`, `!=`, `*`, `!*`                       |
| boolean                 | `=`, `!=`, `*`, `!*`                       |
| integer                 | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |
| smallint                | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |
| bigint                  | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |
| unsignedInteger         | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |
| decimal                 | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |
| float                   | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |
| date                    | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |
| time                    | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |
| datetime                | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |
| guid                    | `=`, `!=`, `*`, `!*`                       |
| text                    | `*`                                        |
| percent                 | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |
| money                   | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |
| money_value             | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |
| currency                | `=`, `!=`, `*`, `!*`                       |
| duration                | `=`, `!=`, `<`, `<=`, `>`, `>=`, `*`, `!*` |

All these filters are implemented by [ComparisonFilter](#comparisonfilter-filter).

See [Enable Advanced Operators for String Filter](./how_to.md#enable-advanced-operators-for-string-filter)
and [Enable Case-insensitive String Filter](./how_to.md#enable-case-insensitive-string-filter) for examples of
advanced filter configuration.

The following filters are also configured automatically:

- The `composite_identifier` filter for the ID field, if an entity has a composite identifier.
  The operators enabled for this filter are `=`, `!=`, `*`, `!*`.
  It is implemented by [CompositeIdentifierFilter](../../Filter/CompositeIdentifierFilter.php).
- The `association` filter for [extended associations](../../../EntityExtendBundle/Resources/doc/associations.md).
  The operators enabled for this filter are `=`, `!=`, `*`, `!*`.
  It is implemented by [ExtendedAssociationFilter](../../Filter/ExtendedAssociationFilter.php).
  More details on how to configure extended associations are available in the following topics:
  [Configure an Extended Many-To-One Association](./how_to.md#configure-an-extended-many-to-one-association),
  [Configure an Extended Many-To-Many Association](./how_to.md#configure-an-extended-many-to-many-association) and
  [Configure an Extended Multiple Many-To-One Association](./how_to.md#configure-an-extended-multiple-many-to-one-association).

A list of filters that should be configured explicitly using
[type](./configuration.md#filters-configuration-section) option:

| Filter Type  | Enabled Operators    | Implemented by |
|--------------|----------------------|----------------|
| primaryField | `=`, `!=`, `*`, `!*` | [PrimaryFieldFilter](../../Filter/PrimaryFieldFilter.php) |

You can also run the `php var/console debug:config oro_api` command to view all the existing filters
in the  `filters` section and all the existing operators for filters in the `filter_operators` section.

## FilterInterface Interface

The [FilterInterface](../../Filter/FilterInterface.php) interface must be implemented by all filters.

Consider checking out the following classes before implementing your own filters, as each of them may serve
as a good base class for your own filters:
[StandaloneFilter](#standalonefilter-base-class),
[StandaloneFilterWithDefaultValue](#standalonefilterwithdefaultvalue-base-class),
[ComparisonFilter](#comparisonfilter-filter) and
[AssociationFilter](../../Filter/AssociationFilter.php).

## CollectionAwareFilterInterface Interface

The [CollectionAwareFilterInterface](../../Filter/CollectionAwareFilterInterface.php) interface must be implemented
by filters that can handle a collection valued association.

Examples of such filters are [ComparisonFilter](#comparisonfilter-filter),
[ExtendedAssociationFilter](../../Filter/ExtendedAssociationFilter.php)
and [PrimaryFieldFilter](../../Filter/PrimaryFieldFilter.php).

## MetadataAwareFilterInterface Interface

The [MetadataAwareFilterInterface](../../Filter/MetadataAwareFilterInterface.php) interface must be implemented
by filters that depends on the [entity metadata](../../Metadata/EntityMetadata.php).

An example of such filter is [CompositeIdentifierFilter](../../Filter/CompositeIdentifierFilter.php).

## RequestAwareFilterInterface Interface

The [RequestAwareFilterInterface](../../Filter/RequestAwareFilterInterface.php) interface must be implemented
by filters that depends on a [request type](./request_type.md).

Examples of such filters are [ExtendedAssociationFilter](../../Filter/ExtendedAssociationFilter.php)
and [CompositeIdentifierFilter](../../Filter/CompositeIdentifierFilter.php).

## SelfIdentifiableFilterInterface Interface

The [SelfIdentifiableFilterInterface](../../Filter/SelfIdentifiableFilterInterface.php) interface must be implemented
by filters that should search their own value by themselves.

An example of such filter is [ExtendedAssociationFilter](../../Filter/ExtendedAssociationFilter.php).

## NamedValueFilterInterface Interface

The [NamedValueFilterInterface](../../Filter/NamedValueFilterInterface.php) interface must be implemented
by filters that have a named value.

An example of such filter is [ExtendedAssociationFilter](../../Filter/ExtendedAssociationFilter.php).

## StandaloneFilter Base Class

The [StandaloneFilter](../../Filter/StandaloneFilter.php) is the base class for filters that can be used
independently of other filters.

Examples of such filters are [ComparisonFilter](#comparisonfilter-filter),
[ExtendedAssociationFilter](../../Filter/ExtendedAssociationFilter.php),
[CompositeIdentifierFilter](../../Filter/CompositeIdentifierFilter.php)
and [PrimaryFieldFilter](../../Filter/PrimaryFieldFilter.php).

## StandaloneFilterWithDefaultValue Base Class

The [StandaloneFilterWithDefaultValue](../../Filter/StandaloneFilterWithDefaultValue.php) is the base class for filters
that can be used independently of other filters and have a predefined default value.

Examples of such filters are [PageNumberFilter](../../Filter/PageNumberFilter.php),
[PageSizeFilter](../../Filter/PageSizeFilter.php) and [SortFilter](../../Filter/SortFilter.php).

## Criteria Class

The [Criteria](../../Collection/Criteria.php) class represents criteria for filtering data returned by ORM queries.
This class extends
[Doctrine Criteria](https://github.com/doctrine/collections/blob/master/lib/Doctrine/Common/Collections/Criteria.php)
class and adds methods to work with joins. It is required because data API filters can be applied to associations
at any nesting level.

## CriteriaConnector Class

The [CriteriaConnector](../../Util/CriteriaConnector.php) class is used to apply criteria stored in Criteria object
to QueryBuilder object.

This class uses [CriteriaNormalizer](../../Util/CriteriaNormalizer.php) class to prepare Criteria object before
criteria are applied to QueryBuilder object.

Also pay attention to [RequireJoinsDecisionMakerInterface](../../Util/RequireJoinsDecisionMakerInterface.php)
and [OptimizeJoinsDecisionMakerInterface](../../Util/OptimizeJoinsDecisionMakerInterface.php) interfaces
and `oro_api.query.require_joins_decision_maker` and `oro_api.query.optimize_joins_decision_maker` services.
You can decorate these services if your expressions require this.

## QueryExpressionVisitor Class

The [QueryExpressionVisitor](../../Collection/QueryExpressionVisitor.php) is used to walk a graph of DQL expressions
from Criteria object and turns them into a query. This class is similar to
[Doctrine QueryExpressionVisitor](https://github.com/doctrine/doctrine2/blob/master/lib/Doctrine/ORM/Query/QueryExpressionVisitor.php),
but allows to add new types of expressions easily and helps to build subquery based expressions.

## Query Expressions

The following query expressions are implemented out of the box:

| Operator        | Class | Description |
|-----------------|-------|-------------|
| AND             | [AndCompositeExpression](../../Collection/QueryVisitorExpression/AndCompositeExpression.php) | Logical AND |
| OR              | [OrCompositeExpression](../../Collection/QueryVisitorExpression/OrCompositeExpression.php) | Logical OR |
| NOT             | [NotCompositeExpression](../../Collection/QueryVisitorExpression/NotCompositeExpression.php) | Logical NOT |
| =               | [EqComparisonExpression](../../Collection/QueryVisitorExpression/EqComparisonExpression.php) | EQUAL TO comparison |
| <>              | [NeqComparisonExpression](../../Collection/QueryVisitorExpression/NeqComparisonExpression.php) | NOT EQUAL TO comparison |
| <               | [LtComparisonExpression](../../Collection/QueryVisitorExpression/LtComparisonExpression.php) | LESS THAN comparison |
| <=              | [LteComparisonExpression](../../Collection/QueryVisitorExpression/LteComparisonExpression.php) | LESS THAN OR EQUAL TO comparison |
| >               | [GtComparisonExpression](../../Collection/QueryVisitorExpression/GtComparisonExpression.php) | GREATER THAN comparison |
| >=              | [GteComparisonExpression](../../Collection/QueryVisitorExpression/GteComparisonExpression.php) | GREATER THAN OR EQUAL TO comparison |
| IN              | [InComparisonExpression](../../Collection/QueryVisitorExpression/InComparisonExpression.php) | IN comparison |
| NIN             | [NinComparisonExpression](../../Collection/QueryVisitorExpression/NinComparisonExpression.php) | NOT IN comparison |
| EXISTS          | [ExistsComparisonExpression](../../Collection/QueryVisitorExpression/ExistsComparisonExpression.php) | EXISTS (IS NOT NULL) and NOT EXISTS (IS NULL) comparisons |
| EMPTY           | [EmptyComparisonExpression](../../Collection/QueryVisitorExpression/EmptyComparisonExpression.php) | EMPTY and NOT EMPTY comparisons for collections |
| NEQ_OR_NULL     | [NeqOrNullComparisonExpression](../../Collection/QueryVisitorExpression/NeqOrNullComparisonExpression.php) | NOT EQUAL TO OR IS NULL comparison |
| NEQ_OR_EMPTY    | [NeqOrEmptyComparisonExpression](../../Collection/QueryVisitorExpression/NeqOrEmptyComparisonExpression.php) | NOT EQUAL TO OR EMPTY comparison for collections |
| MEMBER_OF       | [MemberOfComparisonExpression](../../Collection/QueryVisitorExpression/MemberOfComparisonExpression.php) | MEMBER OF comparison that checks whether a collection contains any of specific values |
| ALL_MEMBER_OF   | [AllMemberOfComparisonExpression](../../Collection/QueryVisitorExpression/AllMemberOfComparisonExpression.php) | ALL MEMBER OF comparison that checks whether a collection contains all of specific values |
| CONTAINS        | [ContainsComparisonExpression](../../Collection/QueryVisitorExpression/ContainsComparisonExpression.php) | LIKE '%value%' comparison |
| NOT_CONTAINS    | [NotContainsComparisonExpression](../../Collection/QueryVisitorExpression/NotContainsComparisonExpression.php) | NOT LIKE '%value%' comparison |
| STARTS_WITH     | [StartsWithComparisonExpression](../../Collection/QueryVisitorExpression/StartsWithComparisonExpression.php) | LIKE 'value%' comparison |
| NOT_STARTS_WITH | [NotStartsWithComparisonExpression](../../Collection/QueryVisitorExpression/NotStartsWithComparisonExpression.php) | NOT LIKE 'value%' comparison |
| ENDS_WITH       | [EndsWithComparisonExpression](../../Collection/QueryVisitorExpression/EndsWithComparisonExpression.php) | LIKE '%value' comparison |
| NOT_ENDS_WITH   | [NotEndsWithComparisonExpression](../../Collection/QueryVisitorExpression/NotEndsWithComparisonExpression.php) | NOT LIKE '%value' comparison |

If necessary, you can add new comparison expressions and use them in your filters.
For this, create a class that implements the expression logic, register it as a service tagged with the
`oro.api.query.comparison_expression` in the dependency injection container
and (if required) decorate the [oro_api.query.require_joins_decision_maker](../../Util/RequireJoinsDecisionMaker.php)
and [oro_api.query.optimize_joins_decision_maker](../../Util/OptimizeJoinsDecisionMaker.php) services.

## Creating New Filter

To create a new filter:

- Create a class that implements the filtering logic. This class must implement
  [FilterInterface](#filterinterface-interface) interface or extend one of the classes that implement this interface.
- If your filter is complex and depends on other services, create a factory to create the filter.
  Register the factory as a service in the dependency injection container.
- Register this class in `oro_api / filters` section using `Resources/config/oro/app.yml`.
  Examples of filters registration can be found in
  [ApiBundle/Resources/config/oro/app.yml](../../Resources/config/oro/app.yml).

To configure your filter to be used for an API resource, use the
[type](./configuration.md#filters-configuration-section) option of the filter.

## Other Classes

Consider checking out the list of other classes below as they can provide insight on how data filtering works:

- [FilterNames](../../Filter/FilterNames.php) - contains names of predefined filters for a specific request type.
- [FilterNamesRegistry](../../Filter/FilterNamesRegistry.php) - the container for names of predefined filters for all registered request types.
- [FilterValue](../../Filter/FilterValue.php) - represents a filter value.
- [FilterValueAccessorInterface](../../Filter/FilterValueAccessorInterface.php) - represents a collection of the FilterValue objects.
- [RestFilterValueAccessor](../../Request/RestFilterValueAccessor.php) - extracts values of filters from REST API HTTP request.
- [FilterHelper](../../Filter/FilterHelper.php) - reusable utility methods that can be used to get filter values.
- [FilterCollection](../../Filter/FilterCollection.php) - a collection of filters.
- [SimpleFilterFactory](../../Filter/SimpleFilterFactory.php) - the default implementation of a factory to create filters.
- [FilterOperatorRegistry](../../Filter/FilterOperatorRegistry.php) - the container for all registered operators for filters.
- [MetaPropertyFilter](../../Filter/MetaPropertyFilter.php) - a filter that is used to request meta properties.
- [AddMetaPropertyFilter](../../Processor/Shared/AddMetaPropertyFilter.php) - a processor that adds "meta" filter that is used to request meta properties.
- [HandleMetaPropertyFilter](../../Processor/Shared/HandleMetaPropertyFilter.php) - a processor that handles "meta" filter.
- [AddMetaProperties](../../Processor/Config/GetConfig/AddMetaProperties.php) - a processor that adds configuration of meta properties requested via "meta" filter.
- [FieldsFilter](../../Filter/FieldsFilter.php) - a filter that is used to filter entity fields.
- [AddFieldsFilter](../../Processor/Shared/AddFieldsFilter.php) - a processor that adds "fields" filters that are used to filter entity fields.
- [HandleFieldsFilter](../../Processor/Shared/HandleFieldsFilter.php) - a processor that handles "fields" filters.
- [FilterFieldsByExtra](../../Processor/Config/Shared/FilterFieldsByExtra.php) - a processor that modifies configuration of entities according to "fields" filters.
- [IncludeFilter](../../Filter/IncludeFilter.php) - a filter that is used to request information about related entities.
- [AddIncludeFilter](../../Processor/Shared/AddIncludeFilter.php) - a processor that adds "include" filter that is used to request information about related entities.
- [HandleIncludeFilter](../../Processor/Shared/HandleIncludeFilter.php) - a processor that handles "include" filter.
- [ExpandRelatedEntities](../../Processor/Config/Shared/ExpandRelatedEntities.php) - a processor that adds configuration of related entities requested via "include" filter.
- [BuildCriteria](../../Processor/Shared/BuildCriteria.php) - a processor that applies all requested filters to the Criteria object.
- [NormalizeFilterValues](../../Processor/Shared/NormalizeFilterValues.php) - a processor that converts values of all requested filters according to the type of the filters and validates that all requested filters are supported.
- [RegisterConfiguredFilters](../../Processor/Shared/RegisterConfiguredFilters.php) - a processor that registers filters according to the [filters](./configuration.md#filters-configuration-section) configuration section.
- [RegisterDynamicFilters](../../Processor/Shared/RegisterDynamicFilters.php) - a processor that registers nested filters.
