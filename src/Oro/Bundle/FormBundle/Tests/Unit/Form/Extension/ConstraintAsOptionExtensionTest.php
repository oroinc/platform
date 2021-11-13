<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\ConstraintAsOptionExtension;
use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ConstraintAsOptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConstraintAsOptionExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new ConstraintAsOptionExtension(new ConstraintFactory());
    }

    public function testConfigureOptions()
    {
        $constraintOptions = [
            new Assert\NotBlank(),
            ['Length' => ['min' => 3]],
            ['Url' => null],
        ];
        $constraintClasses = [
            Assert\NotBlank::class,
            Assert\Length::class,
            Assert\Url::class
        ];

        $resolver = new OptionsResolver();
        $resolver->setDefaults(['constraints' => []]);

        $this->extension->configureOptions($resolver);
        $actualOptions = $resolver->resolve(['constraints' => $constraintOptions]);

        $this->assertArrayHasKey('constraints', $actualOptions);
        foreach ($actualOptions['constraints'] as $key => $constraint) {
            $this->assertInstanceOf($constraintClasses[$key], $constraint);
        }
    }
}
