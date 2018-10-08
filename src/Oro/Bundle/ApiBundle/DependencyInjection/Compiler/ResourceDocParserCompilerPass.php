<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Oro\Component\ChainProcessor\AbstractMatcher;
use Oro\Component\ChainProcessor\ExpressionParser;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers resource documentation parsers for all supported Data API request types.
 */
class ResourceDocParserCompilerPass implements CompilerPassInterface
{
    private const RESOURCE_DOC_PARSER_REGISTRY_SERVICE_ID = 'oro_api.resource_doc_parser_registry';
    private const DEFAULT_RESOURCE_DOC_PARSER_SERVICE_ID  = 'oro_api.resource_doc_parser.template';
    private const RESOURCE_DOC_PARSER_TAG                 = 'oro.api.resource_doc_parser';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // find resource documentation parsers
        $resourceDocParsers = [];
        $taggedServices = $container->findTaggedServiceIds(self::RESOURCE_DOC_PARSER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $container->getDefinition($id)->setPublic(true);
            foreach ($attributes as $tagAttributes) {
                $resourceDocParsers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }

        // sort by priority and flatten
        if (!empty($resourceDocParsers)) {
            $resourceDocParsers = DependencyInjectionUtil::sortByPriorityAndFlatten($resourceDocParsers);
        }

        // add non defined explicitly parsers
        $existingRequestType = [];
        foreach ($resourceDocParsers as list($id, $expr)) {
            if ($expr) {
                $existingRequestType[$this->normalizeRequestType($expr)] = true;
            }
        }
        $defaultResourceDocParsers = [];
        $views = $this->getApiDocViews($container);
        foreach ($views as $name => $view) {
            if (!empty($view['request_type'])) {
                $expr = $this->getNormalizeRequestTypeExpr($view['request_type']);
                if (!isset($existingRequestType[$expr])) {
                    $existingRequestType[$expr] = true;
                    $id = self::DEFAULT_RESOURCE_DOC_PARSER_SERVICE_ID . '.' . $name;
                    $container
                        ->setDefinition($id, new ChildDefinition(self::DEFAULT_RESOURCE_DOC_PARSER_SERVICE_ID))
                        ->setPublic(true);
                    $defaultResourceDocParsers[] = [$id, \implode('&', $view['request_type'])];
                }
            }
        }
        $resourceDocParsers = \array_merge($defaultResourceDocParsers, $resourceDocParsers);

        // register
        $container->getDefinition(self::RESOURCE_DOC_PARSER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $resourceDocParsers);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
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
        \rsort($aspects, SORT_STRING);

        return \implode('&', $aspects);
    }

    /**
     * @param string $requestTypeExpr
     *
     * @return string
     */
    private function normalizeRequestType(string $requestTypeExpr): string
    {
        $requestType = ExpressionParser::parse($requestTypeExpr);
        if (\is_string($requestType) || AbstractMatcher::OPERATOR_AND !== \key($requestType)) {
            return $requestTypeExpr;
        }

        $isNormalizable = true;
        $aspects = \current($requestType);
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
