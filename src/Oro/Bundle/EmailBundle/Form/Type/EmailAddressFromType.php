<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailAddressFromType extends AbstractType
{
    const NAME = 'oro_email_email_address_from';

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /** @var MailboxManager */
    protected $mailboxManager;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     * @param RelatedEmailsProvider  $relatedEmailsProvider
     * @param MailboxManager         $mailboxManager
     */
    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        RelatedEmailsProvider $relatedEmailsProvider,
        MailboxManager $mailboxManager
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->mailboxManager = $mailboxManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = $this->createChoices();

        $resolver->setDefaults([
            'choices'   => $choices,
            'read_only' => count($choices) === 1,
        ]);
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_select2_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
