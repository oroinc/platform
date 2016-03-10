<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Checks permission to delete object.
 */
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

        if (!$this->securityFacade->isGranted('DELETE', $object)) {
            throw new AccessDeniedHttpException('You have no access to delete given record');
        }
    }
}
