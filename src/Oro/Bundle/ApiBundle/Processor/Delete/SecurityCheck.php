<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SecurityCheck implements ProcessorInterface
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var DeleteContext $context */

        if (!$context->hasObject()) {
            // context has no object
            return;
        }

        $object = $context->getObject();

        if (!is_object($object)) {
            // given object data is not an object
            return;
        }

        if ($context->isSecurityChecked()) {
            //security already checked
            return;
        }

        if (!$this->securityFacade->isGranted($object, 'DELETE')) {
            throw new AccessDeniedHttpException();
        }

        $context->setSecurityChecked();
    }
}