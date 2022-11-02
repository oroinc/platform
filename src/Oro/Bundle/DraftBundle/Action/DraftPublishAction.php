<?php

namespace Oro\Bundle\DraftBundle\Action;

use Oro\Bundle\DraftBundle\Manager\DraftManager;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Responsible for draft publishing.
 */
class DraftPublishAction extends AbstractDraftAction
{
    /**
     * @var DraftManager
     */
    private $draftManager;

    public function __construct(ContextAccessor $contextAccessor, DraftManager $draftManager)
    {
        parent::__construct($contextAccessor);
        $this->draftManager = $draftManager;
    }

    /**
     * @param \ArrayAccess $context
     */
    protected function executeAction($context): void
    {
        $source = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_SOURCE]);
        $target = $this->draftManager->createPublication($source, $context);
        $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_TARGET], $target);
    }
}
