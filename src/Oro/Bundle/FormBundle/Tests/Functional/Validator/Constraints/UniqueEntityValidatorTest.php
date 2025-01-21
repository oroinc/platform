<?php

namespace Oro\Bundle\FormBundle\Tests\Functional\Validator\Constraints;

use Oro\Bundle\FormBundle\Validator\Constraints\UniqueEntity;
use Oro\Bundle\FormBundle\Validator\Constraints\UniqueEntityValidator;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @dbIsolationPerTest
 */
class UniqueEntityValidatorTest extends WebTestCase
{
    private UniqueEntityValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->validator = self::getContainer()->get('oro_form.test.validator_constraints.unique_entity');
    }

    private function createContext(Constraint $constraint): ExecutionContext
    {
        $validator = $this->createMock(ValidatorInterface::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->willReturnArgument(0);

        $context = new ExecutionContext($validator, 'root', $translator);
        $context->setGroup('MyGroup');
        $context->setNode('InvalidValue', null, null, 'property.path');
        $context->setConstraint($constraint);

        $validator->expects(self::any())
            ->method('inContext')
            ->with($context)
            ->willReturn($this->createMock(ContextualValidatorInterface::class));

        return $context;
    }

    private function validate(Constraint $constraint, ExecutionContextInterface $context): void
    {
        $entity1 = new Product();
        $entity1->setName('Foo');

        $entity2 = new Product();
        $entity2->setName('Foo');

        $this->validator->initialize($context);
        $this->validator->validate($entity1, $constraint);

        $violationsCount = count($context->getViolations());
        self::assertSame(0, $violationsCount, sprintf('0 violation expected. Got %u.', $violationsCount));

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($entity1);
        $em->flush();

        $this->validator->initialize($context);
        $this->validator->validate($entity1, $constraint);

        $violationsCount = count($context->getViolations());
        self::assertSame(0, $violationsCount, sprintf('0 violation expected. Got %u.', $violationsCount));

        $this->validator->validate($entity2, $constraint);
    }

    public function testValidateViolationsAtEntityLevel(): void
    {
        $constraint = new UniqueEntity([
            'message' => 'myMessage',
            'fields' => ['name'],
            'em' => 'default',
            'buildViolationAtEntityLevel' => true
        ]);
        $context = $this->createContext($constraint);

        $this->validate($constraint, $context);

        self::assertCount(
            1,
            $context->getViolations(),
            sprintf('1 violation expected. Got %u.', count($context->getViolations()))
        );
        self::assertEquals(
            new ConstraintViolation(
                'myMessage',
                'myMessage',
                [
                    '{{ unique_key }}' => '"name"',
                    '{{ unique_fields }}' => '"oro.testframework.product.name.label"'
                ],
                'root',
                'property.path',
                'InvalidValue',
                null,
                UniqueEntity::NOT_UNIQUE_ERROR,
                $constraint
            ),
            $context->getViolations()->get(0)
        );
    }

    public function testValidateViolationsAtPropertyPathLevel(): void
    {
        $constraint = new UniqueEntity([
            'message' => 'myMessage',
            'fields' => ['name'],
            'em' => 'default',
            'buildViolationAtEntityLevel' => false,
        ]);
        $context = $this->createContext($constraint);

        $this->validate($constraint, $context);

        self::assertCount(
            1,
            $context->getViolations(),
            sprintf('1 violation expected. Got %u.', count($context->getViolations()))
        );
        self::assertEquals(
            new ConstraintViolation(
                'myMessage',
                'myMessage',
                [
                    '{{ unique_key }}' => '"name"',
                    '{{ unique_fields }}' => '"oro.testframework.product.name.label"',
                ],
                'root',
                'property.path.name',
                'Foo',
                null,
                UniqueEntity::NOT_UNIQUE_ERROR,
                $constraint
            ),
            $context->getViolations()->get(0)
        );
    }
}
