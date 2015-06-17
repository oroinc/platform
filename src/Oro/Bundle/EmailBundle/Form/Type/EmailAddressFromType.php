<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;

class EmailAddressFromType extends AbstractType
{
    const NAME = 'oro_email_email_address_from';

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'choices' => $this->createChoices(),
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

        $emails = array_map(function (Email $email) {
            return $email->getEmail();
        }, $user->getEmails()->toArray());

        $allEmails = array_merge([$user->getEmail()], $emails);

        return array_combine($allEmails, $allEmails);
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
        return static::NAME;
    }
}
