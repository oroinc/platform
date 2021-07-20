<?php

namespace Oro\Bundle\DraftBundle\Action;

use Oro\Bundle\DraftBundle\Manager\DraftManager;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Responsible for draft create.
 */
class DraftCreateAction extends AbstractDraftAction
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
        $target = $this->draftManager->createDraft($source, $context);
        $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_TARGET], $target);
    }
}
