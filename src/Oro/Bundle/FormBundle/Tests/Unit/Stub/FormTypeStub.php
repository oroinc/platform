<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Tests\Unit\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormTypeStub extends AbstractType
{
    private array $options;

    private string $parent;

    public function __construct(array $options, string $parent = FormType::class)
    {
        $this->options = $options;
        $this->parent = $parent;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        foreach ($this->options as $option) {
            $resolver->define($option);
        }
    }

    #[\Override]
    public function getParent(): string
    {
        return $this->parent;
    }
}
