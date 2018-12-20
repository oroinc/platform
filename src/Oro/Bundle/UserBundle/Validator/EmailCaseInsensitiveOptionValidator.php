<?php

namespace Oro\Bundle\UserBundle\Validator;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Validator\Constraints\EmailCaseInsensitiveOptionConstraint;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates a case insensitive option which can be edited in system configuration.
 * Cannot be disabled for MySql with case insensitive collation.
 * Cannot be enabled for database with user which have duplications by email in lowercase.
 */
class EmailCaseInsensitiveOptionValidator extends ConstraintValidator
{
    private const LIMIT = 10;

    /** @var UserManager */
    private $userManager;

    /** @var TranslatorInterface */
    private $translator;

    /** @var DatagridRouteHelper */
    private $datagridRouteHelper;

    /**
     * @param UserManager $userManager
     * @param TranslatorInterface $translator
     * @param DatagridRouteHelper $datagridRouteHelper
     */
    public function __construct(
        UserManager $userManager,
        TranslatorInterface $translator,
        DatagridRouteHelper $datagridRouteHelper
    ) {
        $this->userManager = $userManager;
        $this->translator = $translator;
        $this->datagridRouteHelper = $datagridRouteHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EmailCaseInsensitiveOptionConstraint) {
            throw new UnexpectedTypeException($constraint, EmailCaseInsensitiveOptionConstraint::class);
        }

        if (!$value) {
            $this->checkCaseSensitivity($constraint, $value);
        } else {
            $this->checkDuplicatedEmails($constraint, $value);
        }
    }

    /**
     * @param EmailCaseInsensitiveOptionConstraint $constraint
     * @param bool $value
     */
    private function checkCaseSensitivity(EmailCaseInsensitiveOptionConstraint $constraint, $value)
    {
        $repository = $this->getRepository();
        if (!$repository->isCaseInsensitiveCollation()) {
            return;
        }

        $this->context->buildViolation($constraint->collationMessage)
            ->setInvalidValue($value)
            ->addViolation();
    }

    /**
     * @param EmailCaseInsensitiveOptionConstraint $constraint
     * @param bool $value
     */
    private function checkDuplicatedEmails(EmailCaseInsensitiveOptionConstraint $constraint, $value)
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

    /**
     * @param array $emails
     * @return string
     */
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

    /**
     * @return UserRepository
     */
    private function getRepository()
    {
        return $this->userManager->getRepository();
    }
}
