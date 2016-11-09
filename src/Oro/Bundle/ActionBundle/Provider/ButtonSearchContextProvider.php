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

        return $this->handlerButtonSearchContext(new ButtonSearchContext(), $context);
    }

    /**
     * @param ButtonSearchContext $buttonSearchContext
     * @param $context
     *
     * @return ButtonSearchContext
     */
    protected function handlerButtonSearchContext(ButtonSearchContext $buttonSearchContext, $context)
    {
        $buttonSearchContext->setEntity(
            $context[ContextHelper::ENTITY_CLASS_PARAM],
            $context[ContextHelper::ENTITY_ID_PARAM]
        );
        $buttonSearchContext->setGridName($context[ContextHelper::DATAGRID_PARAM]);
        $buttonSearchContext->setGroup($context[ContextHelper::GROUP_PARAM]);
        $buttonSearchContext->setReferrer($context[ContextHelper::FROM_URL_PARAM]);
        $buttonSearchContext->setRouteName($context[ContextHelper::ROUTE_PARAM]);

        return $buttonSearchContext;
    }
}
