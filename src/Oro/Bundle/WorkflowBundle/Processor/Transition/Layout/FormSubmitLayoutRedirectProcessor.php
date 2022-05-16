<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition\Layout;

use Oro\Bundle\SecurityBundle\Util\SameSiteUrlHelper;
use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Sets redirect response as result of workflow processing.
 */
class FormSubmitLayoutRedirectProcessor implements ProcessorInterface
{
    private SameSiteUrlHelper $sameSiteUrlHelper;

    public function __construct(SameSiteUrlHelper $sameSiteUrlHelper)
    {
        $this->sameSiteUrlHelper = $sameSiteUrlHelper;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        if (!$context->isSaved() || !$context->getResultType() instanceof LayoutResultTypeInterface) {
            return;
        }

        foreach ($this->lookUpRedirectUrl($context) as $url) {
            if ($url) {
                $context->setResult(new RedirectResponse(urldecode($url)));
                $context->setProcessed(true);
                break;
            }
        }
    }

    /**
     * @param TransitionContext $context
     * @return \Generator|string[]|null[]
     */
    protected function lookUpRedirectUrl(TransitionContext $context)
    {
        yield $context->getWorkflowItem()->getResult()->get('redirectUrl');

        $request = $context->getRequest();

        yield $request->get('originalUrl');

        yield $this->sameSiteUrlHelper->getSameSiteReferer($request);

        yield '/';
    }
}
