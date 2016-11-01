<?php

namespace Oro\Bundle\UserBundle\Validator;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Validator\Constraints\UsedPassword;

class UsedPasswordValidator extends ConstraintValidator
{
    /** @var Registry */
    protected $registry;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EncoderFactoryInterface */
    protected $encoderFactory;

    public function __construct(
        Registry $registry,
        ConfigManager $configManager,
        EncoderFactoryInterface $encoderFactory
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($user, Constraint $constraint)
    {
        if (!$constraint instanceof UsedPassword) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'Constraint\NotBlank');
        }

        if (!$this->configManager->get('oro_user.used_password_check_enabled')) {
            return;
        }

        $passwordHistoryLimit = $this->configManager->get('oro_user.used_password_check_number');

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
