<?php

namespace Oro\Bundle\UIBundle\Placeholder;

use Oro\Bundle\UIBundle\Placeholder\Filter\PlaceholderFilterInterface;

use Oro\Component\Config\Resolver\ResolverInterface;

class PlaceholderProvider
{
    /** @var ResolverInterface */
    protected $resolver;

    /** @var array */
    protected $placeholders;

    /** @var PlaceholderFilterInterface[] */
    protected $filters;

    /**
     * @param array                        $placeholders
     * @param ResolverInterface            $resolver
     * @param PlaceholderFilterInterface[] $filters
     */
    public function __construct(array $placeholders, ResolverInterface $resolver, array $filters)
    {
        $this->placeholders = $placeholders;
        $this->resolver     = $resolver;
        $this->filters      = $filters;
    }

    /**
     * Get items by placeholder name
     *
     * @param string $placeholderName
     * @param array  $variables
     * @return array
     */
    public function getPlaceholderItems($placeholderName, array $variables)
    {
        $result = [];

        $items = isset($this->placeholders[$placeholderName]['items'])
            ? $this->placeholders[$placeholderName]['items']
            : array();
        foreach ($items as $item) {
            $item = $this->resolver->resolve($item, $variables);
            if (!isset($item['applicable']) || $item['applicable'] === true) {
                $result[] = $item;
            }
        }

        foreach ($this->filters as $filter) {
            $result = $filter->filter($result, $variables);
        }

        return $result;
    }
}
