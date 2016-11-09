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

        return $this->handleButtonSearchContext(new ButtonSearchContext(), $context);
    }

    /**
     * @param ButtonSearchContext $buttonSearchContext
     * @param $context
     *
     * @return ButtonSearchContext
     */
    protected function handleButtonSearchContext(ButtonSearchContext $buttonSearchContext, $context)
    {
        return $buttonSearchContext
            ->setEntity($context[ContextHelper::ENTITY_CLASS_PARAM], $context[ContextHelper::ENTITY_ID_PARAM])
            ->setGridName($context[ContextHelper::DATAGRID_PARAM])
            ->setGroup($context[ContextHelper::GROUP_PARAM])
            ->setReferrer($context[ContextHelper::FROM_URL_PARAM])
            ->setRouteName($context[ContextHelper::ROUTE_PARAM]);
    }
}
