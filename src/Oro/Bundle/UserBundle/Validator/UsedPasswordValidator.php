<?php

namespace Oro\Bundle\UserBundle\Validator;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use Oro\Bundle\UserBundle\Validator\Constraints\UsedPassword;
use Oro\Bundle\UserBundle\Provider\UsedPasswordConfigProvider;

class UsedPasswordValidator extends ConstraintValidator
{
    /** @var Registry */
    protected $registry;

    /** @var UsedPasswordConfigProvider */
    protected $configProvider;

    /** @var EncoderFactoryInterface */
    protected $encoderFactory;

    /**
     * @param Registry $registry
     * @param UsedPasswordConfigProvider $configProvider
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct(
        Registry $registry,
        UsedPasswordConfigProvider $configProvider,
        EncoderFactoryInterface $encoderFactory
    ) {
        $this->registry = $registry;
        $this->configProvider = $configProvider;
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($user, Constraint $constraint)
    {
        if (!$constraint instanceof UsedPassword) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'Constraint\UsedPassword');
        }

        if (!$this->configProvider->isUsedPasswordCheckEnabled()) {
            return;
        }

        $passwordHistoryLimit = $this->configProvider->getUsedPasswordsCheckNumber();

        $oldPasswords = $this->registry
            ->getManagerForClass('OroUserBundle:PasswordHistory')
            ->getRepository('OroUserBundle:PasswordHistory')
            ->findBy(['user' => $user], null, $passwordHistoryLimit);

        $encoder = $this->encoderFactory->getEncoder($user);
        $newPassword = $user->getPlainPassword();

        foreach ($oldPasswords as $passwordHistory) {
            if ($encoder->isPasswordValid($passwordHistory->getPasswordHash(), $newPassword, $passwordHistory->getSalt())) {
                $this->context->addViolation($constraint->message);

                break;
            }
        }
    }
}
