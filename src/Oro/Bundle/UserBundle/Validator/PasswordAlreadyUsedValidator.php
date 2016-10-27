<?php

namespace Oro\Bundle\UserBundle\Validator;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordAlreadyUsed;

class PasswordAlreadyUsedValidator extends ConstraintValidator
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

        $encoder = null;
        foreach ($oldPasswords as $passwordHash) {
            if (!$encoder) {
                $encoder = $this->encoderFactory->getEncoder($passwordHash->getUser());
            }

            if ($encoder->isPasswordValid($passwordHash->getHash(), $value, $passwordHash->getSalt())) {
                $this->context->addViolation($constraint->message);

                break;
            }
        }
    }
}
