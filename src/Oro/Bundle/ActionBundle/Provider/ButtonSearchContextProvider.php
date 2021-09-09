<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;

/**
 * Provides the search context needed to find actions buttons.
 */
class ButtonSearchContextProvider
{
    protected ContextHelper $contextHelper;

    public function __construct(ContextHelper $contextHelper)
    {
        $this->contextHelper = $contextHelper;
    }

    public function getButtonSearchContext(array $context = null): ButtonSearchContext
    {
        return $this->buildFromContext($this->contextHelper->getContext($context));
    }

    protected function buildFromContext(array $context): ButtonSearchContext
    {
        $buttonSearchContext = new ButtonSearchContext();

        return $buttonSearchContext
            ->setEntity($context[ContextHelper::ENTITY_CLASS_PARAM], $context[ContextHelper::ENTITY_ID_PARAM])
            ->setDatagrid($context[ContextHelper::DATAGRID_PARAM])
            ->setGroup($context[ContextHelper::GROUP_PARAM])
            ->setReferrer($context[ContextHelper::FROM_URL_PARAM])
            ->setRouteName((string)$context[ContextHelper::ROUTE_PARAM]);
    }
}
