<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The configuration of elements in "entities" section.
 */
class EntityDefinitionConfiguration extends TargetEntityDefinitionConfiguration
{
    private const UPSERT = 'upsert';
    private const UPSERT_DISABLE = 'disable';
    private const UPSERT_ADD = 'add';
    private const UPSERT_REMOVE = 'remove';
    private const UPSERT_REPLACE = 'replace';

    /**
     * {@inheritDoc}
     */
    public function configureEntityNode(NodeBuilder $node): void
    {
        parent::configureEntityNode($node);
        $node
            ->arrayNode(ConfigUtil::IDENTIFIER_FIELD_NAMES)
                ->performNoDeepMerging()
                ->prototype('scalar')->cannotBeEmpty()->end()
            ->end()
            ->booleanNode(ConfigUtil::DISABLE_INCLUSION)->end()
            ->booleanNode(ConfigUtil::DISABLE_FIELDSET)->end()
            ->arrayNode(ConfigUtil::DISABLE_META_PROPERTIES)
                ->treatFalseLike([false])
                ->treatTrueLike([true])
                ->prototype('scalar')->end()
            ->end()
            ->booleanNode(ConfigUtil::DISABLE_PARTIAL_LOAD)->end()
            ->arrayNode(ConfigUtil::INNER_JOIN_ASSOCIATIONS)
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode(ConfigUtil::DOCUMENTATION_RESOURCE)
                ->beforeNormalization()
                    ->ifString()
                    ->then(function ($v) {
                        return [$v];
                    })
                ->end()
                ->prototype('scalar')->cannotBeEmpty()->end()
            ->end();

        /** @var NodeBuilder $upsertNode */
        $upsertNode = $node
            ->arrayNode(self::UPSERT)
                ->treatFalseLike([self::UPSERT_DISABLE => true])
                ->treatTrueLike([self::UPSERT_DISABLE => false])
                ->children()
                    ->booleanNode(self::UPSERT_DISABLE)->end();
        $this->appendArrayOfNotEmptyStrings($upsertNode, self::UPSERT_ADD);
        $this->appendArrayOfNotEmptyStrings($upsertNode, self::UPSERT_REMOVE);
        $this->appendArrayOfNotEmptyStrings($upsertNode, self::UPSERT_REPLACE);
    }

    /**
     * {@inheritDoc}
     */
    protected function postProcessConfig(array $config): array
    {
        $config = parent::postProcessConfig($config);
        if (empty($config[ConfigUtil::IDENTIFIER_FIELD_NAMES])) {
            unset($config[ConfigUtil::IDENTIFIER_FIELD_NAMES]);
        }
        if (empty($config[ConfigUtil::INNER_JOIN_ASSOCIATIONS])) {
            unset($config[ConfigUtil::INNER_JOIN_ASSOCIATIONS]);
        }
        if (empty($config[ConfigUtil::DOCUMENTATION_RESOURCE])) {
            unset($config[ConfigUtil::DOCUMENTATION_RESOURCE]);
        }
        if (empty($config[ConfigUtil::DISABLE_META_PROPERTIES])) {
            unset($config[ConfigUtil::DISABLE_META_PROPERTIES]);
        }
        if (\array_key_exists(self::UPSERT, $config)) {
            if (empty($config[self::UPSERT][self::UPSERT_ADD])) {
                unset($config[self::UPSERT][self::UPSERT_ADD]);
            }
            if (empty($config[self::UPSERT][self::UPSERT_REMOVE])) {
                unset($config[self::UPSERT][self::UPSERT_REMOVE]);
            }
            if (empty($config[self::UPSERT][self::UPSERT_REPLACE])) {
                unset($config[self::UPSERT][self::UPSERT_REPLACE]);
            }
        }

        return $config;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureFieldNode(NodeBuilder $node): void
    {
        parent::configureFieldNode($node);
        $node
            ->booleanNode(ConfigUtil::META_PROPERTY)->end();
    }

    private function appendArrayOfNotEmptyStrings(NodeBuilder $node, string $name): void
    {
        $node->arrayNode($name)
            ->variablePrototype()
                ->validate()
                    ->always(function (mixed $value) {
                        if (!\is_array($value)) {
                            throw new \InvalidArgumentException(sprintf(
                                'Expected "array", but got "%s"',
                                get_debug_type($value)
                            ));
                        }
                        foreach ($value as $val) {
                            if (!\is_string($val) || '' === $val) {
                                throw new \InvalidArgumentException('Expected array of not empty strings');
                            }
                        }

                        return $value;
                    });
    }
}
