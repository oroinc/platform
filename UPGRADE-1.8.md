UPGRADE FROM 1.7 to 1.8
=======================

####ImportExportBundle
 - `Oro\Bundle\ImportExportBundle\Context\ContextInterface` added $incrementBy integer parameter for methods: incrementReadCount, incrementAddCount, incrementUpdateCount, incrementReplaceCount, incrementDeleteCount, incrementErrorEntriesCount

####WorkflowBundle
 Migrate conditions logic to ConfigExpression component:
 - Removed `src/Oro/Bundle/WorkflowBundle/Model/Condition/ConditionInterface`, `Oro\Component\ConfigExpression\ExpressionInterface` should be used instead
 - Removed `src/Oro/Bundle/WorkflowBundle/Model/Condition/ConditionFactory`, `Oro\Component\ConfigExpression\ExpressionFactory` should be used instead
 - Removed `src/Oro/Bundle/WorkflowBundle/Model/Condition/ConditionAssembler`, `Oro\Component\ConfigExpression\ExpressionAssembler` should be used instead
 - Removed `src/Oro/Bundle/WorkflowBundle/Model/Condition/AbstractCondition` and all conditions, `Oro\Component\ConfigExpression\Condition\AbstractCondition` and corresponding expressions from ConfigExpression component should be used instead
