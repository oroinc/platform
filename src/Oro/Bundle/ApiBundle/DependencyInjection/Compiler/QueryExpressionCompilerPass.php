<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all possible ORM expressions that can be used in Criteria object.
 * @see \Oro\Bundle\ApiBundle\Util\CriteriaConnector::applyCriteria
 */
class QueryExpressionCompilerPass implements CompilerPassInterface
{
    use ApiTaggedServiceTrait;

    private const QUERY_EXPRESSION_VISITOR_FACTORY_SERVICE_ID = 'oro_api.query.expression_visitor_factory';

    private const COMPOSITE_EXPRESSION_TAG = 'oro.api.query.composite_expression';
    private const COMPOSITE_EXPRESSION_TYPE = 'type';
    private const COMPARISON_EXPRESSION_TAG = 'oro.api.query.comparison_expression';
    private const COMPARISON_EXPRESSION_OPERATOR = 'operator';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $compositeExpressions = $this->getExpressions(
            $container,
            self::COMPOSITE_EXPRESSION_TAG,
            self::COMPOSITE_EXPRESSION_TYPE
        );
        $comparisonExpressions = $this->getExpressions(
            $container,
            self::COMPARISON_EXPRESSION_TAG,
            self::COMPARISON_EXPRESSION_OPERATOR
        );
        $container->getDefinition(self::QUERY_EXPRESSION_VISITOR_FACTORY_SERVICE_ID)
            ->replaceArgument(0, $compositeExpressions)
            ->replaceArgument(1, $comparisonExpressions);
    }

    private function getExpressions(ContainerBuilder $container, string $tagName, string $operatorPlaceholder): array
    {
        $services = [];
        $taggedServices = $container->findTaggedServiceIds($tagName);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $services[$this->getPriorityAttribute($attributes)][] = [
                    $this->getRequiredAttribute($attributes, $operatorPlaceholder, $id, $tagName),
                    new Reference($id)
                ];
            }
        }
        if (empty($services)) {
            return [];
        }

        $expressions = [];
        $services = $this->sortByPriorityAndFlatten($services);
        foreach ($services as [$expressionType, $serviceRef]) {
            $expressions[$expressionType] = $serviceRef;
        }

        return $expressions;
    }
}
