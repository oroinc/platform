<?php

namespace Oro\Bundle\UserBundle\Validator;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordAlreadyUsed;

class PasswordAlreadyUsedValidator extends ConstraintValidator
{
    /** @var Registry */
    protected $registry;

    /** @var ConfigManager */
    protected $configManager;

    /** @var UserPasswordEncoder */
    protected $passwordEncoder;

    public function __construct(Registry $registry, ConfigManager $configManager, UserPasswordEncoder $passwordEncoder)
    {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof PasswordAlreadyUsed) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'Constraint\NotBlank');
        }

        if (null === $constraint->userId || !$this->configManager->get('oro_user.match_old_passwords_enabled')) {
            return;
        }

        $passwordHistoryLimit = $this->configManager->get('oro_user.match_old_passwords_number');

        $oldPasswords = $this->registry
            ->getManagerForClass('OroUserBundle:PasswordHash')
            ->getRepository('OroUserBundle:PasswordHash')
            ->findBy(['user' => $constraint->userId], null, $passwordHistoryLimit);

        foreach ($oldPasswords as $passwordHash) {
            $this->passwordEncoder->isPasswordValid($passwordHash->getUser(), $passwordHash->getHash());
        }

        $this->context->addViolation($constraint->message);
    }
}
