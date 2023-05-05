<?php

namespace Oro\Bundle\ApiBundle\Form;

use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\ResolvedFormTypeFactoryInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

/**
 * Creates ResolvedFormTypeInterface instances for regular and API forms.
 */
class ApiResolvedFormTypeFactory implements ResolvedFormTypeFactoryInterface
{
    private ResolvedFormTypeFactoryInterface $defaultFactory;
    private FormExtensionCheckerInterface $formExtensionChecker;

    public function __construct(
        ResolvedFormTypeFactoryInterface $defaultFactory,
        FormExtensionCheckerInterface $formExtensionChecker
    ) {
        $this->defaultFactory = $defaultFactory;
        $this->formExtensionChecker = $formExtensionChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function createResolvedType(
        FormTypeInterface $type,
        array $typeExtensions,
        ResolvedFormTypeInterface $parent = null
    ): ResolvedFormTypeInterface {
        $resolvedType = $this->defaultFactory->createResolvedType($type, $typeExtensions, $parent);
        if ($this->formExtensionChecker->isApiFormExtensionActivated()) {
            $resolvedType = new ApiResolvedFormType($resolvedType);
        }

        return $resolvedType;
    }
}
