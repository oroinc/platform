<?php

declare(strict_types=1);

namespace Oro\Component\Testing\Unit\Form\Extension\Validator;

use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\Extension\Validator\Constraints\Form as FormConstraint;
use Symfony\Component\Form\Extension\Validator\Type;
use Symfony\Component\Form\Extension\Validator\ValidatorTypeGuesser;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Traverse;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form validator extension that avoids duplicate Form/Traverse constraints.
 */
final class ValidatorExtension extends AbstractExtension
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly bool $legacyErrorMessages = true,
        private readonly ?FormRendererInterface $formRenderer = null,
        private readonly ?TranslatorInterface $translator = null,
    ) {
        $this->registerConstraints();
    }

    private function registerConstraints(): void
    {
        /** @var ClassMetadata $metadata */
        $metadata = $this->validator->getMetadataFor(Form::class);

        // Gather name of assigned class constraints, to check later if Form/Traverse constraints are already added.
        $existing = array_map(fn (Constraint $constraint) => get_class($constraint), $metadata->getConstraints());

        if (!in_array(FormConstraint::class, $existing, true)) {
            $metadata->addConstraint(new FormConstraint());
        }

        if (!in_array(Traverse::class, $existing, true)) {
            $metadata->addConstraint(new Traverse(false));
        }
    }

    #[\Override]
    public function loadTypeGuesser(): ?FormTypeGuesserInterface
    {
        return new ValidatorTypeGuesser($this->validator);
    }

    #[\Override]
    protected function loadTypeExtensions(): array
    {
        return [
            new Type\FormTypeValidatorExtension(
                $this->validator,
                $this->legacyErrorMessages,
                $this->formRenderer,
                $this->translator
            ),
            new Type\RepeatedTypeValidatorExtension(),
            new Type\SubmitTypeValidatorExtension(),
        ];
    }
}
