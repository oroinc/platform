<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

class QueryExpressionCompilerPass implements CompilerPassInterface
{
    const QUERY_EXPRESSION_VISITOR_FACTORY_SERVICE_ID = 'oro_api.query.expression_visitor_factory';

    const COMPOSITE_EXPRESSION_TAG  = 'oro.api.query.composite_expression';
    const COMPOSITE_EXPRESSION_TYPE = 'type';

    const COMPARISON_EXPRESSION_TAG      = 'oro.api.query.comparison_expression';
    const COMPARISON_EXPRESSION_OPERATOR = 'operator';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $visitorServiceDef = DependencyInjectionUtil::findDefinition(
            $container,
            self::QUERY_EXPRESSION_VISITOR_FACTORY_SERVICE_ID
        );

        if (null === $visitorServiceDef) {
            return;
        }

        // register expressions
        $visitorServiceDef->setArguments([
            $this->getExpressions($container, self::COMPOSITE_EXPRESSION_TAG, self::COMPOSITE_EXPRESSION_TYPE),
            $this->getExpressions($container, self::COMPARISON_EXPRESSION_TAG, self::COMPARISON_EXPRESSION_OPERATOR)
        ]);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $tagName
     * @param string           $operatorPlaceholder
     *
     * @return array [operator name => provider definition, ...]
     */
    protected function getExpressions(ContainerBuilder $container, $tagName, $operatorPlaceholder)
    {
        $expressions = [];
        // find services
        $services = [];
        $taggedServices = $container->findTaggedServiceIds($tagName);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $tag) {
                $expressionType = $tag[$operatorPlaceholder];
                $priority = isset($tag['priority']) ? $tag['priority'] : 0;
                $services[$priority][] = [$operatorPlaceholder => $expressionType, 'definition' => new Reference($id)];
            }
        }
        if (empty($services)) {
            return $expressions;
        }

        // sort by priority and flatten
        krsort($services);
        $services = call_user_func_array('array_merge', $services);

        foreach ($services as $serviceInfo) {
            $expressions[$serviceInfo[$operatorPlaceholder]] = $serviceInfo['definition'];
        }

        return $expressions;
    }
}
