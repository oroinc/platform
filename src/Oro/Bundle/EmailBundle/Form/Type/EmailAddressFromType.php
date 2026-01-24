<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting the sender email address in email composition.
 *
 * Provides a dropdown field for selecting from available email addresses including user emails
 * and mailbox emails, with read-only enforcement when only one option is available.
 */
class EmailAddressFromType extends AbstractType
{
    const NAME = 'oro_email_email_address_from';

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /** @var MailboxManager */
    protected $mailboxManager;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        RelatedEmailsProvider $relatedEmailsProvider,
        MailboxManager $mailboxManager
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->mailboxManager = $mailboxManager;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = $this->createChoices();

        $resolver->setDefaults([
            'choices'   => $choices,
            'attr' => []
        ]);

        $resolver->setNormalizer('attr', function (Options $options, $value) {
            $value['readonly'] = (count($options['choices']) === 1);

            return $value;
        });
    }

    /**
     * @return array
     */
    protected function createChoices()
    {
        $user = $this->tokenAccessor->getUser();
        if (!$user instanceof User) {
            return [];
        }

        $emails = array_merge(
            array_values($this->relatedEmailsProvider->getEmails($user, 1, true)),
            $this->mailboxManager->findAvailableMailboxEmails($user, $this->tokenAccessor->getOrganization())
        );

        return array_combine($emails, $emails);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2ChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
