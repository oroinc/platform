<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Processor\EnumOption;

use Oro\Bundle\ApiBundle\Api\Repository\EnumOptionRepository;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Creates a new enum option and sets data taken from an identifier in the "enum_code.option_id" format.
 */
class CreateEnumOptionFromId implements ProcessorInterface
{
    public function __construct(
        private readonly EnumOptionRepository $repository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        $option = $context->getResult();
        if (!$option instanceof EnumOption) {
            return;
        }

        $id = (string)$context->getId();
        $newOption = $this->repository->createOptionFromId($id);
        if (null === $newOption) {
            $context->addError(
                Error::createValidationError(
                    Constraint::ENTITY_ID,
                    \sprintf(
                        'Unexpected enum option identifier "%s". Expected a valid "enum_code.option_id" value.',
                        $id
                    )
                )->setSource(ErrorSource::createByPropertyPath('id'))
            );

            return;
        }

        $context->setResult($newOption);
    }
}
