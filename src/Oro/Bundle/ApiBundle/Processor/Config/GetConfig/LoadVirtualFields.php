<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\Label;
use Oro\Bundle\ApiBundle\Provider\ConfigExtra;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;

class LoadVirtualFields implements ProcessorInterface
{
    /** @var VirtualFieldProviderInterface */
    protected $virtualFieldProvider;

    /**
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     */
    public function __construct(VirtualFieldProviderInterface $virtualFieldProvider)
    {
        $this->virtualFieldProvider = $virtualFieldProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        /** @var array $definition */
        $definition = $context->getResult();
        if (empty($definition) || !array_key_exists(ConfigUtil::FIELDS, $definition)) {
            // virtual fields is added only if a definition of fields exists
            return;
        }

        $entityClass   = $context->getClassName();
        $virtualFields = $this->virtualFieldProvider->getVirtualFields($entityClass);
        if (!empty($virtualFields)) {
            foreach ($virtualFields as $field) {
                $query        = $this->virtualFieldProvider->getVirtualFieldQuery($entityClass, $field);
                $propertyPath = $this->getPropertyPath($query);
                if (!empty($propertyPath)) {
                    $definition[ConfigUtil::FIELDS][$field][ConfigUtil::PROPERTY_PATH] = $propertyPath;
                    if (!empty($query['select']['label']) && $context->hasExtra(ConfigExtra::DESCRIPTIONS)) {
                        $definition[ConfigUtil::FIELDS][$field][ConfigUtil::LABEL] = new Label(
                            $query['select']['label']
                        );
                    }
                }
            }
            $context->setResult($definition);
        }
    }

    /**
     * Extracts a property path from a virtual field query if the given query is supported
     *
     * @param array $virtualFieldQuery
     *
     * @return string[]|null
     */
    protected function getPropertyPath($virtualFieldQuery)
    {
        if (empty($virtualFieldQuery['join'])
            || empty($virtualFieldQuery['select']['expr'])
            || !preg_match('/^\w+\.\w+$/', $virtualFieldQuery['select']['expr'])
        ) {
            // unsupported virtual field
            return null;
        }

        $joins = $this->getJoins($virtualFieldQuery);
        if (empty($joins)) {
            // a virtual field has unsupported joins
            return null;
        }

        $result   = [];
        $pair     = explode('.', $virtualFieldQuery['select']['expr']);
        $result[] = $pair[1];
        while (isset($joins[$pair[0]])) {
            $pair     = explode('.', $joins[$pair[0]]);
            $result[] = $pair[1];
        }

        return implode('.', array_reverse($result));
    }

    /**
     * Extracts all joins from a virtual field query
     *
     * @param array $virtualFieldQuery
     *
     * @return array|null [alias => join, ...]
     */
    protected function getJoins($virtualFieldQuery)
    {
        $joins = [];
        foreach (['left', 'inner'] as $joinType) {
            if (!empty($virtualFieldQuery['join'][$joinType])) {
                foreach ($virtualFieldQuery['join'][$joinType] as $join) {
                    if (!empty($join['condition']) || !preg_match('/^\w+\.\w+$/', $join['join'])) {
                        // unsupported virtual field
                        return null;
                    }
                    $joins[$join['alias']] = $join['join'];
                }
            }
        }

        return $joins;
    }
}
