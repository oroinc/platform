<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Form\DataTransformer\OriginTransformer;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The from type for EmailOrigin entity.
 */
class EmailOriginFromType extends AbstractType
{
    public function __construct(
        private TokenAccessorInterface $tokenAccessor,
        private EmailModelBuilderHelper $helper,
        private MailboxManager $mailboxManager,
        private ManagerRegistry $doctrine,
        private EmailOriginHelper $emailOriginHelper
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new OriginTransformer($this->doctrine, $this->tokenAccessor, $this->emailOriginHelper)
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = $this->createChoices();
        $resolver->setDefaults([
            'choices' => $choices,
            'attr' => [],
        ]);

        $resolver->setNormalizer('attr', function (Options $options, $value) {
            $value['readonly'] = (count($options['choices']) === 1);

            return $value;
        });
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2ChoiceType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_email_email_origin_from';
    }

    private function createChoices(): array
    {
        $user = $this->tokenAccessor->getUser();
        if (!$user instanceof User) {
            return [];
        }

        return $this->fillMailboxOrigins($user, $this->fillUserOrigins($user) ?: $this->fillUserEmails($user));
    }

    private function fillUserOrigins(User $user): array
    {
        $origins = [];
        $userOrigins = $user->getEmailOrigins();
        foreach ($userOrigins as $origin) {
            if (($origin instanceof UserEmailOrigin) && $origin->isActive()) {
                $owner = $origin->getOwner();
                $email = $origin->getOwner()->getEmail();
                $this->helper->preciseFullEmailAddress($email, ClassUtils::getClass($owner), $owner->getId());
                $origins[$email] = $origin->getId() . '|' . $origin->getOwner()->getEmail();
            }
        }

        return $origins;
    }

    private function fillUserEmails(User $user): array
    {
        $email = $user->getEmail();
        $origins = $this->processFillUserEmail($email, [], $user);
        $userEmails = $user->getEmails();
        foreach ($userEmails as $email) {
            $email = $email->getEmail();
            $origins = $this->processFillUserEmail($email, $origins);
        }

        return $origins;
    }

    private function processFillUserEmail($email, array $origins, ?User $owner = null): array
    {
        $key = '0|' . $email;
        if (!\array_key_exists($key, $origins)) {
            if ($owner) {
                $this->helper->preciseFullEmailAddress($email, ClassUtils::getClass($owner), $owner->getId());
            } else {
                $this->helper->preciseFullEmailAddress($email);
            }
            $origins[$email] = $key;
        }

        return $origins;
    }

    private function fillMailboxOrigins(User $user, array $origins): array
    {
        $mailboxes = $this->mailboxManager->findAvailableMailboxes($user, $this->tokenAccessor->getOrganization());
        foreach ($mailboxes as $mailbox) {
            $origin = $mailbox->getOrigin();
            /**
             * if in mailbox configuration neither of IMAP or SMTP was configured, origin will be NULL
             */
            if ($origin && $origin->isActive()) {
                $email = $mailbox->getEmail();
                $this->helper->preciseFullEmailAddress($email);
                $email .= ' (Mailbox)';
                $origins[$email] = $origin->getId() . '|' . $mailbox->getEmail();
            }
        }

        return $origins;
    }
}
