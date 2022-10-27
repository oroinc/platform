<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Validator\Constraints\EmailCaseInsensitiveOption;
use Oro\Bundle\UserBundle\Validator\Constraints\EmailCaseInsensitiveOptionValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailCaseInsensitiveOptionValidatorTest extends ConstraintValidatorTestCase
{
    /** @var UserRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $userRepository;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var DatagridRouteHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridRouteHelper;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->datagridRouteHelper = $this->createMock(DatagridRouteHelper::class);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function createValidator()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        return new EmailCaseInsensitiveOptionValidator(
            $doctrine,
            $this->translator,
            $this->datagridRouteHelper
        );
    }

    public function testValidateExceptions()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            sprintf('Expected argument of type "%s"', EmailCaseInsensitiveOption::class)
        );

        $this->validator->validate('', $this->createMock(Constraint::class));
    }

    /**
     * @dataProvider validateValidDataProvider
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

        $constraint = new EmailCaseInsensitiveOption();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function validateValidDataProvider(): array
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

        $constraint = new EmailCaseInsensitiveOption();
        $this->validator->validate(false, $constraint);

        $this->buildViolation($constraint->collationMessage)
            ->setInvalidValue(false)
            ->assertRaised();
    }

    public function testValidateInvalidDuplicatedEmails()
    {
        $constraint = new EmailCaseInsensitiveOption();

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
            ->with($constraint->duplicatedEmailsClickHere, [], 'validators')
            ->willReturnArgument(0);

        $this->validator->validate(true, $constraint);

        $this->buildViolation($constraint->duplicatedEmailsMessage)
            ->setParameter(
                '%click_here%',
                sprintf('<a href="some/link/to/grid">%s</a>', $constraint->duplicatedEmailsClickHere)
            )
            ->setInvalidValue(true)
            ->assertRaised();
    }
}
