<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates a case insensitive option which can be edited in system configuration.
 * Cannot be disabled for MySql with case insensitive collation.
 * Cannot be enabled for database with user which have duplications by email in lowercase.
 */
class EmailCaseInsensitiveOptionValidator extends ConstraintValidator
{
    private const LIMIT = 10;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var TranslatorInterface */
    private $translator;

    /** @var DatagridRouteHelper */
    private $datagridRouteHelper;

    public function __construct(
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        DatagridRouteHelper $datagridRouteHelper
    ) {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->datagridRouteHelper = $datagridRouteHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EmailCaseInsensitiveOption) {
            throw new UnexpectedTypeException($constraint, EmailCaseInsensitiveOption::class);
        }

        if ($value) {
            $this->checkDuplicatedEmails($constraint, $value);
        } else {
            $this->checkCaseSensitivity($constraint, $value);
        }
    }

    private function checkCaseSensitivity(EmailCaseInsensitiveOption $constraint, mixed $value): void
    {
        $repository = $this->getRepository();
        if (!$repository->isCaseInsensitiveCollation()) {
            return;
        }

        $this->context->buildViolation($constraint->collationMessage)
            ->setInvalidValue($value)
            ->addViolation();
    }

    private function checkDuplicatedEmails(EmailCaseInsensitiveOption $constraint, mixed $value): void
    {
        $emails = $this->getRepository()->findLowercaseDuplicatedEmails(self::LIMIT);
        if (!$emails) {
            return;
        }

        $clickHere = sprintf(
            '<a href="%s">%s</a>',
            $this->buildLink($emails),
            $this->translator->trans($constraint->duplicatedEmailsClickHere, [], 'validators')
        );

        $this->context->buildViolation($constraint->duplicatedEmailsMessage)
            ->setParameters(['%click_here%' => $clickHere])
            ->setInvalidValue($value)
            ->addViolation();
    }

    private function buildLink(array $emails): string
    {
        return $this->datagridRouteHelper->generate(
            'oro_user_index',
            'users-grid',
            [
                AbstractFilterExtension::MINIFIED_FILTER_PARAM => [
                    'email' => [
                        'type' => TextFilterType::TYPE_IN,
                        'value' => implode(',', $emails),
                    ]
                ]
            ]
        );
    }

    private function getRepository(): UserRepository
    {
        return $this->doctrine->getRepository(User::class);
    }
}
