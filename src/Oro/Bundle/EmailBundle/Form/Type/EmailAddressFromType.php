<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;

class EmailAddressFromType extends AbstractType
{
    const NAME = 'oro_email_email_address_from';

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /** @var MailboxManager */
    protected $mailboxManager;

    /**
     * @param SecurityFacade $securityFacade
     * @param RelatedEmailsProvider $relatedEmailsProvider
     * @param MailboxManager $mailboxManager
     */
    public function __construct(
        SecurityFacade $securityFacade,
        RelatedEmailsProvider $relatedEmailsProvider,
        MailboxManager $mailboxManager
    ) {
        $this->securityFacade = $securityFacade;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->mailboxManager = $mailboxManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
        $user = $this->securityFacade->getLoggedUser();
        if (!$user instanceof User) {
            return [];
        }

        $emails = array_merge(
            array_values($this->relatedEmailsProvider->getEmails($user, 1, true)),
            $this->mailboxManager->findAvailableMailboxEmails($user, $this->securityFacade->getOrganization())
        );

        return array_combine($emails, $emails);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_choice';
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
