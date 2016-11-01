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
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UsedPassword) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'Constraint\NotBlank');
        }

        if (null === $constraint->userId || !$this->configManager->get('oro_user.used_password_check_enabled')) {
            return;
        }

        $passwordHistoryLimit = $this->configManager->get('oro_user.used_password_check_number');

        $oldPasswords = $this->registry
            ->getManagerForClass('OroUserBundle:PasswordHistory')
            ->getRepository('OroUserBundle:PasswordHistory')
            ->findBy(['user' => $constraint->userId], null, $passwordHistoryLimit);

        $user = $this->registry
            ->getManagerForClass('OroUserBundle:User')
            ->find('OroUserBundle:User', $constraint->userId);
        $encoder = $this->encoderFactory->getEncoder($user);

        foreach ($oldPasswords as $passwordHistory) {
            if ($encoder->isPasswordValid($passwordHistory->getPasswordHash(), $value, $passwordHistory->getSalt())) {
                $this->context->addViolation($constraint->message);

                break;
            }
        }
    }
}
