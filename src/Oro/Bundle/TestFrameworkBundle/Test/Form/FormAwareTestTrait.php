<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Test\Form;

use Symfony\Component\Form\FormInterface;

/**
 * Provides handy methods for testing a form in a functional test.
 */
trait FormAwareTestTrait
{
    protected static function assertFormOptions(
        FormInterface $form,
        array $options = []
    ): void {
        self::assertArrayIntersectEquals(
            $options,
            $form->getConfig()->getOptions(),
            'The form options are not as expected'
        );
    }

    protected static function assertFormHasField(
        FormInterface $form,
        string $name,
        string $formType,
        array $options = []
    ): void {
        self::assertTrue($form->has($name), 'Field "' . $name . '" is not present in form');
        self::assertEquals(
            $formType,
            get_class($form->get($name)->getConfig()->getType()->getInnerType()),
            'The form type of field "' . $name . '" is not as expected'
        );

        if ($options) {
            self::assertArrayIntersectEquals(
                $options,
                $form->get($name)->getConfig()->getOptions(),
                'The options of field "' . $name . '" are not as expected'
            );
        }
    }
}
