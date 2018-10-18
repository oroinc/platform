<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Validator\Constraints\EmailCaseInsensitiveOptionConstraint;
use Oro\Bundle\UserBundle\Validator\EmailCaseInsensitiveOptionValidator;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class EmailCaseInsensitiveOptionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $userRepository;

    /** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var DatagridRouteHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridRouteHelper;

    /** @var EmailCaseInsensitiveOptionValidator */
    private $validator;

    /** @var EmailCaseInsensitiveOptionConstraint */
    private $constraint;

    /** @var ConstraintViolationBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $violationBuilder;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $executionContext;

    protected function setUp()
    {
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->userManager = $this->createMock(UserManager::class);
        $this->userManager->expects($this->any())->method('getRepository')->willReturn($this->userRepository);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->datagridRouteHelper = $this->createMock(DatagridRouteHelper::class);

        $this->validator = new EmailCaseInsensitiveOptionValidator(
            $this->userManager,
            $this->translator,
            $this->datagridRouteHelper
        );

        $this->constraint = new EmailCaseInsensitiveOptionConstraint();

        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->violationBuilder->expects($this->any())->method('setInvalidValue')->willReturnSelf();
        $this->violationBuilder->expects($this->any())->method('addViolation')->willReturnSelf();

        $this->executionContext = $this->createMock(ExecutionContextInterface::class);
    }

    public function testValidateExceptions()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            sprintf('Expected argument of type "%s"', EmailCaseInsensitiveOptionConstraint::class)
        );

        /** @var Constraint $constraint */
        $constraint = $this->createMock(Constraint::class);

        $this->validator->initialize($this->executionContext);
        $this->validator->validate('', $constraint);
    }

    /**
     * @dataProvider validateValidDataProvider
     *
     * @param bool $value
     * @param bool $isCaseInsensitiveCollation
     * @param array $emails
     */
    public function testValidateValid(bool $value, bool $isCaseInsensitiveCollation, array $emails)
    {
        $this->userRepository->expects($this->any())
            ->method('isCaseInsensitiveCollation')
            ->willReturn($isCaseInsensitiveCollation);

        $this->userRepository->expects($this->any())
            ->method('findLowercaseDuplicatedEmails')
            ->with(10)
            ->willReturn($emails);

        $this->executionContext->expects($this->never())
            ->method('buildViolation');

        $this->validator->initialize($this->executionContext);
        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateValidDataProvider()
    {
        return [
            [
                'value' => false,
                'isCaseInsensitiveCollation' => false,
                'emails' => []
            ],
            [
                'value' => true,
                'isCaseInsensitiveCollation' => false,
                'emails' => []
            ],
            [
                'value' => true,
                'isCaseInsensitiveCollation' => true,
                'emails' => []
            ],
        ];
    }

    public function testValidateInvalidCaseInsensitive()
    {
        $this->userRepository->expects($this->once())
            ->method('isCaseInsensitiveCollation')
            ->willReturn(true);

        $this->userRepository->expects($this->never())
            ->method('findLowercaseDuplicatedEmails');

        $this->datagridRouteHelper->expects($this->never())
            ->method('generate');

        $this->translator->expects($this->never())
            ->method('trans');

        $this->executionContext->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->collationMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->initialize($this->executionContext);
        $this->validator->validate(false, $this->constraint);
    }

    public function testValidateInvalidDuplicatedEmails()
    {
        $this->userRepository->expects($this->never())
            ->method('isCaseInsensitiveCollation');

        $this->userRepository->expects($this->once())
            ->method('findLowercaseDuplicatedEmails')
            ->with(10)
            ->willReturn(['test@example.com']);

        $this->datagridRouteHelper->expects($this->once())
            ->method('generate')
            ->with(
                'oro_user_index',
                'users-grid',
                [
                    AbstractFilterExtension::MINIFIED_FILTER_PARAM => [
                        'email' => [
                            'type' => TextFilterType::TYPE_IN,
                            'value' => implode(',', ['test@example.com']),
                        ]
                    ]
                ]
            )
            ->willReturn('some/link/to/grid');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with($this->constraint->duplicatedEmailsClickHere, [], 'validators')
            ->willReturnArgument(0);

        $this->executionContext->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->duplicatedEmailsMessage)
            ->willReturn($this->violationBuilder);

        $this->violationBuilder->expects($this->once())
            ->method('setParameters')
            ->with(
                [
                    '%click_here%' => sprintf(
                        '<a href="some/link/to/grid">%s</a>',
                        $this->constraint->duplicatedEmailsClickHere
                    )
                ]
            )
            ->willReturnSelf();

        $this->validator->initialize($this->executionContext);
        $this->validator->validate(true, $this->constraint);
    }
}
