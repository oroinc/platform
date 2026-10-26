<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Processor\EnumOption;

use Oro\Bundle\ApiBundle\Api\Repository\EnumOptionRepository;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Checks that the enum exists and allows the requested option creation or update.
 */
class ValidateEnumOptionModificationAccess implements ProcessorInterface
{
    public function __construct(
        private readonly EnumOptionRepository $repository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $form = $context->getForm();
        /** @var EnumOption $enumOption */
        $enumOption = $form->getData();

        $configs = $this->repository->getConfigs($enumOption->getEnumCode());
        if (!$configs) {
            FormUtil::addNamedFormError(
                $form,
                'enum existence constraint',
                \sprintf('The enum "%s" does not exist.', $enumOption->getEnumCode()),
            );

            return;
        }

        if (ApiAction::CREATE !== $context->getParentAction()) {
            return;
        }

        if ($this->repository->isSystemEnum($configs)) {
            FormUtil::addNamedFormError(
                $form,
                AccessDeniedException::class,
                \sprintf('Options cannot be added to the system enum "%s".', $enumOption->getEnumCode()),
                null,
                Response::HTTP_FORBIDDEN
            );

            return;
        }

        if ($this->repository->isImmutableEnum($configs)) {
            FormUtil::addNamedFormError(
                $form,
                AccessDeniedException::class,
                \sprintf('Options cannot be added to the immutable enum "%s".', $enumOption->getEnumCode()),
                null,
                Response::HTTP_FORBIDDEN
            );
        }
    }
}
