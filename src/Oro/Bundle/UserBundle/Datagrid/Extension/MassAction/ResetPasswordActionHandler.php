<?php

namespace Oro\Bundle\UserBundle\Datagrid\Extension\MassAction;

use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\NotificationBundle\Model\EmailNotification;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ResetPasswordActionHandler implements MassActionHandlerInterface
{
    const TEMPLATE_NAME = 'force_reset_password';

    /** @var EntityManager */
    protected $em;

    /** @var EmailNotificationManager */
    protected $notificationManager;

    /** @var UserManager */
    protected $userManager;

    /** @var LoggerInterface */
    protected $logger;

    /** @var TranslatorInterface  */
    protected $translator;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EmailTemplateInterface */
    protected $template = null;

    /** @var User */
    protected $user = null;

    /** @var string */
    protected $successMessage = 'oro.user.password.force_reset.mass_action.success';

    /** @var string */
    protected $errorMessage = 'oro.user.password.force_reset.mass_action.failure';

    /**
     * @param EmailNotificationManager $notificationManager
     * @param UserManager $userManager
     * @param TranslatorInterface $translator
     * @param LoggerInterface $logger
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        EmailNotificationManager $notificationManager,
        UserManager $userManager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        SecurityFacade $securityFacade
    ) {
        $this->notificationManager = $notificationManager;
        $this->userManager = $userManager;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        // current user will be processed last
        $processCurrent = false;
        $currentUser = $this->getCurrentUser();
        $currentUserId = $currentUser ? $currentUser->getId() : null;
        $massActionOptions = $args->getMassAction()->getOptions();

        /** @var IterableResult $results */
        $results = $args->getResults();
        $results->rewind();

        $this->em = $results->getSource()->getEntityManager();
        $this->template = $this->em->getRepository('OroEmailBundle:EmailTemplate')
            ->findOneBy(['name' => self::TEMPLATE_NAME]);
        while ($record = $results->current()) {
            /** @var ResultRecord $record */
            $user = $record->getRootEntity();
            if (!$user instanceof User) {
                // hydration failed
                $responseMessage = $massActionOptions->offsetGetByPath('[messages][failure]', $this->errorMessage);

                return new MassActionResponse(false, $this->translator->trans($responseMessage));
            }

            if ($currentUserId === $user->getId()) {
                $processCurrent = true;
                $results->next();

                continue;
            }

            $this->disableLoginAndNotify($user);
            $results->next();
        }

        if ($processCurrent) {
            $this->disableLoginAndNotify($currentUser);
        }

        $this->em->flush();

        $responseMessage = $massActionOptions->offsetGetByPath('[messages][success]', $this->successMessage);

        return new MassActionResponse(true, $this->translator->trans($responseMessage));
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * @param User $user
     */
    protected function disableLoginAndNotify(User $user)
    {
        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($user->generateToken());
        }

        $user->setLoginDisabled(true);
        $this->userManager->updateUser($user, false);

        try {
            $passResetNotification = new EmailNotification($this->template, [$user->getEmail()]);
            $this->notificationManager->process($user, [$passResetNotification], $this->logger);
            $this->em->flush();
        } catch (\Exception $e) {
            if (null !== $this->logger) {
                $this->logger->error(sprintf('Sending email to %s failed.', $user->getEmail()));
                $this->logger->error($e->getMessage());
            }
        }
    }
}
