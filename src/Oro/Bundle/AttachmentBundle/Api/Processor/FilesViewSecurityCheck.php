<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Checks if it is allowed to view File entities and removes those which are no allowed.
 */
class FilesViewSecurityCheck implements ProcessorInterface
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */
        $filesData = $context->getResult();
        if (!is_array($filesData) && !$filesData instanceof \Traversable) {
            return;
        }

        $viewGranted = [];
        foreach ($filesData as $fileData) {
            $objectIdentity = new ObjectIdentity($fileData['id'], File::class);
            if ($this->authorizationChecker->isGranted('VIEW', $objectIdentity)) {
                $viewGranted[] = $fileData;
            }
        }

        $context->setResult($viewGranted);
    }
}
