<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Gets "scope" filter from a request, validates it and adds to the context.
 */
class GetScope implements ProcessorInterface
{
    public const CONTEXT_PARAM = '_scope';

    private array $scopes;

    public function __construct(array $scopes)
    {
        $this->scopes = $scopes;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        /** @var StandaloneFilterWithDefaultValue $scopeFilter */
        $scopeFilter = $context->getFilters()->get(AddScopeFilter::FILTER_KEY);
        $scopeFilterValue = $context->getFilterValues()->getOne(AddScopeFilter::FILTER_KEY);
        if (null !== $scopeFilterValue) {
            $scope = $scopeFilterValue->getValue();
            if (!\in_array($scope, $this->scopes, true)) {
                $context->addError(
                    Error::createValidationError(
                        Constraint::FILTER,
                        sprintf('Unknown configuration scope. Permissible values: %s.', implode(', ', $this->scopes))
                    )->setSource(ErrorSource::createByParameter(AddScopeFilter::FILTER_KEY))
                );
            }
        } else {
            $scope = $scopeFilter->getDefaultValue();
        }
        if (!$context->hasErrors()) {
            $context->set(self::CONTEXT_PARAM, $scope);
        }
    }
}
