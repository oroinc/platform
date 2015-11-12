<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class NormalizeEntityAlias implements ProcessorInterface
{
    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var NormalizeValueContext $context */

        if (!$context->hasRequirement()) {
            $context->setRequirement('[a-zA-Z]\w+');
        }
        if ($context->hasResult()) {
            $value = $context->getResult();
            if (null !== $value && false === strpos($value, '\\')) {
                $context->setResult($this->entityAliasResolver->getClassByAlias($value));
            }
        }
    }
}
