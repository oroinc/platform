<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;

class ButtonSearchContextProvider
{
    /** @var ContextHelper */
    protected $contextHelper;

    /**
     * @param ContextHelper $contextHelper
     */
    public function __construct(ContextHelper $contextHelper)
    {
        $this->contextHelper = $contextHelper;
    }

    /**
     * @return ButtonSearchContext
     */
    public function getButtonSearchContext()
    {
        $context = $this->contextHelper->getContext();

        return $this->buildFromContext($context);
    }

    /**
     * @param array $context
     *
     * @return ButtonSearchContext
     */
    public function buildFromContext(array $context)
    {
        $buttonSearchContext = new ButtonSearchContext();

        if (isset($context[ContextHelper::ENTITY_CLASS_PARAM])) {
            if (isset($context[ContextHelper::ENTITY_CLASS_PARAM])) {
                $buttonSearchContext
                    ->setEntity($context[ContextHelper::ENTITY_CLASS_PARAM], $context[ContextHelper::ENTITY_ID_PARAM]);
            } else {
                $buttonSearchContext->setEntity($context[ContextHelper::ENTITY_CLASS_PARAM]);
            }
        }

        if (isset($context[ContextHelper::DATAGRID_PARAM])) {
            $buttonSearchContext->setGridName($context[ContextHelper::DATAGRID_PARAM]);
        }

        if (isset($context[ContextHelper::GROUP_PARAM])) {
            $buttonSearchContext->setGroup($context[ContextHelper::GROUP_PARAM]);
        }

        if (isset($context[ContextHelper::FROM_URL_PARAM])) {
            $buttonSearchContext->setReferrer($context[ContextHelper::FROM_URL_PARAM]);
        }

        if (isset($context[ContextHelper::ROUTE_PARAM])) {
            $buttonSearchContext->setRouteName($context[ContextHelper::ROUTE_PARAM]);
        }

        return $buttonSearchContext;
    }
}
