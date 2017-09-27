UPGRADE FROM 2.4 to 2.5
=======================

ActivityListBundle
------------------
  `oroactivity.activityCondition` jQuery widget replaced with `ActivityConditionView` Backbone view,
      removed unused extensions support in its options

DataAuditBundle
---------------
  `oroauditquerydesigner.dataAuditCondition` jQuery widget replaced with `DataAuditConditionView` Backbone view

QueryDesignerBundle
-------------------
  `oroauditquerydesigner.aggregatedFieldCondition` jQuery widget replaced with `AggregatedFieldConditionView` Backbone view
  `oroquerydesigner.fieldCondition` jQuery widget refactored into `AbstractConditionView` and `FieldConditionView` Backbone views

SegmentBundle
------------------
  `orosegment.segmentCondition` jQuery widget replaced with `SegmentConditionView` Backbone view
