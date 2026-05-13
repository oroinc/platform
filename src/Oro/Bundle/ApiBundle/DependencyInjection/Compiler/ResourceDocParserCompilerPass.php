<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Oro\Component\ChainProcessor\AbstractMatcher;
use Oro\Component\ChainProcessor\ExpressionParser;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers resource documentation parsers for all supported API request types.
 */
class ResourceDocParserCompilerPass implements CompilerPassInterface
{
    use ApiTaggedServiceTrait;

    private const RESOURCE_DOC_PARSER_REGISTRY_SERVICE_ID = 'oro_api.resource_doc_parser_registry';
    private const RESOURCE_DOC_PARSER_TEMPLATE_SERVICE_ID = 'oro_api.resource_doc_parser.template';
    private const RESOURCE_DOC_PARSER_TAG = 'oro.api.resource_doc_parser';
    private const DEFAULT_PRIORITY = -5;

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        // find resource documentation parsers
        $services = [];
        $resourceDocParsersGroupedByPriority = [];
        $taggedServices = $container->findTaggedServiceIds(self::RESOURCE_DOC_PARSER_TAG);
        foreach ($taggedServices as $id => $tags) {
            $services[$id] = new Reference($id);
            foreach ($tags as $attributes) {
                $resourceDocParsersGroupedByPriority[$this->getPriorityAttribute($attributes)][] = [
                    $id,
                    $this->getRequestTypeAttribute($attributes)
                ];
            }
        }

        // add non defined explicitly parsers
        $existingRequestTypes = $this->getExistingRequestTypes($resourceDocParsersGroupedByPriority);
        $defaultResourceDocParsers = [];
        $views = $this->getApiDocViews($container);
        foreach ($views as $name => $view) {
            if (!empty($view['request_type'])) {
                $expr = $this->getNormalizeRequestTypeExpr($view['request_type']);
                if (!isset($existingRequestTypes[$expr])) {
                    $existingRequestTypes[$expr] = true;
                    $id = self::RESOURCE_DOC_PARSER_TEMPLATE_SERVICE_ID . '.' . $name;
                    $container->setDefinition($id, new ChildDefinition(self::RESOURCE_DOC_PARSER_TEMPLATE_SERVICE_ID));
                    $services[$id] = new Reference($id);
                    $defaultResourceDocParsers[] = [$id, implode('&', $view['request_type'])];
                }
            }
        }
        $resourceDocParsers = $this->mergeResourceDocParsers(
            $resourceDocParsersGroupedByPriority,
            $defaultResourceDocParsers
        );

        // register
        $container->getDefinition(self::RESOURCE_DOC_PARSER_REGISTRY_SERVICE_ID)
            ->setArgument('$parsers', $resourceDocParsers)
            ->setArgument('$container', ServiceLocatorTagPass::register($container, $services));
    }

    private function getExistingRequestTypes(array $resourceDocParsersGroupedByPriority): array
    {
        if (!$resourceDocParsersGroupedByPriority) {
            return [];
        }

        $existingRequestTypes = [];
        $resourceDocParsers = $this->sortByPriorityAndFlatten($resourceDocParsersGroupedByPriority);
        foreach ($resourceDocParsers as [$id, $expr]) {
            if ($expr) {
                $existingRequestTypes[$this->normalizeRequestType($expr)] = true;
            }
        }

        return $existingRequestTypes;
    }

    private function mergeResourceDocParsers(
        array $resourceDocParsersGroupedByPriority,
        array $defaultResourceDocParsers
    ): array {
        if ($defaultResourceDocParsers) {
            if (isset($resourceDocParsersGroupedByPriority[self::DEFAULT_PRIORITY])) {
                $resourceDocParsersGroupedByPriority[self::DEFAULT_PRIORITY] = array_merge(
                    $resourceDocParsersGroupedByPriority[self::DEFAULT_PRIORITY],
                    $defaultResourceDocParsers
                );
            } else {
                $resourceDocParsersGroupedByPriority[self::DEFAULT_PRIORITY] = $defaultResourceDocParsers;
            }
        }
        if (!$resourceDocParsersGroupedByPriority) {
            return [];
        }

        return $this->sortByPriorityAndFlatten($resourceDocParsersGroupedByPriority);
    }

    private function getApiDocViews(ContainerBuilder $container): array
    {
        $config = DependencyInjectionUtil::getConfig($container);

        return $config['api_doc_views'];
    }

    /**
     * @param string[] $aspects
     *
     * @return string
     */
    private function getNormalizeRequestTypeExpr(array $aspects): string
    {
        rsort($aspects, SORT_STRING);

        return implode('&', $aspects);
    }

    private function normalizeRequestType(string $requestTypeExpr): string
    {
        $requestType = ExpressionParser::parse($requestTypeExpr);
        if (\is_string($requestType) || AbstractMatcher::OPERATOR_AND !== key($requestType)) {
            return $requestTypeExpr;
        }

        $isNormalizable = true;
        $aspects = current($requestType);
        foreach ($aspects as $aspect) {
            if (!\is_string($aspect)) {
                $isNormalizable = false;
                break;
            }
        }
        if (!$isNormalizable) {
            return $requestTypeExpr;
        }

        return $this->getNormalizeRequestTypeExpr($aspects);
    }
}
