<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Form\DataTransformer\OriginTransformer;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailOriginFromType extends AbstractType
{
    const NAME = 'oro_email_email_origin_from';

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var EmailModelBuilderHelper */
    protected $helper;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /** @var MailboxManager */
    protected $mailboxManager;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var EmailOriginHelper */
    protected $emailOriginHelper;

    /**
     * @param TokenAccessorInterface  $tokenAccessor
     * @param RelatedEmailsProvider   $relatedEmailsProvider
     * @param EmailModelBuilderHelper $helper
     * @param MailboxManager          $mailboxManager
     * @param ManagerRegistry         $registry
     * @param EmailOriginHelper       $emailOriginHelper
     */
    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        RelatedEmailsProvider $relatedEmailsProvider,
        EmailModelBuilderHelper $helper,
        MailboxManager $mailboxManager,
        ManagerRegistry $registry,
        EmailOriginHelper $emailOriginHelper
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->helper = $helper;
        $this->mailboxManager = $mailboxManager;
        $this->registry = $registry;
        $this->emailOriginHelper = $emailOriginHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new OriginTransformer(
                $this->registry->getManager(),
                $this->tokenAccessor,
                $this->emailOriginHelper
            )
        );
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * @return array
     */
    protected function createChoices()
    {
        $user = $this->tokenAccessor->getUser();
        if (!$user instanceof User) {
            return [];
        }
        $origins = [];
        $origins = $this->fillUserOrigins($user, $origins);
        if (count($origins) === 0) {
            $origins = $this->fillUserEmails($user, $origins);
        }
        $origins = $this->fillMailboxOrigins($user, $origins);

        return $origins;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return Select2ChoiceType::class;
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

    /**
     * @param User $user
     * @param array $origins
     *
     * @return array
     */
    protected function fillUserOrigins(User $user, $origins)
    {
        $userOrigins = $user->getEmailOrigins();
        foreach ($userOrigins as $origin) {
            if ($origin instanceof UserEmailOrigin) {
                if ($origin->isActive()) {
                    $owner = $origin->getOwner();
                    $email = $origin->getOwner()->getEmail();
                    $this->helper->preciseFullEmailAddress($email, ClassUtils::getClass($owner), $owner->getId());
                    $origins[$email] = $origin->getId() . '|' . $origin->getOwner()->getEmail();
                }
            }
        }
        return $origins;
    }

    /**
     * @param User $user
     * @param array $origins
     * @return array
     */
    protected function fillUserEmails(User $user, $origins)
    {
        $email = $user->getEmail();
        $origins = $this->processFillUserEmail($email, $origins, $user);

        $userEmails = $user->getEmails();
        foreach ($userEmails as $email) {
            $email = $email->getEmail();
            $origins = $this->processFillUserEmail($email, $origins);
        }

        return $origins;
    }

    /**
     * @param string $email
     * @param array $origins
     * @param $owner
     *
     * @return mixed
     */
    protected function processFillUserEmail($email, $origins, $owner = null)
    {
        $key = '0|' . $email;
        if (!array_key_exists($key, $origins)) {
            if ($owner) {
                $this->helper->preciseFullEmailAddress($email, ClassUtils::getClass($owner), $owner->getId());
            } else {
                $this->helper->preciseFullEmailAddress($email);
            }
            $origins[$email] = $key;
        }

        return $origins;
    }

    /**
     * @param User $user
     * @param array $origins
     *
     * @return mixed
     */
    protected function fillMailboxOrigins(User $user, $origins)
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
