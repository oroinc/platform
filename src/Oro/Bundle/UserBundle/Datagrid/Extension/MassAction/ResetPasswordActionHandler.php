<?php

namespace Oro\Bundle\UserBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;

use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;

class ResetPasswordActionHandler implements MassActionHandlerInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var Processor */
    protected $mailerProcessor;

    /** @var UserManager */
    protected $userManager;

    /** @var LoggerInterface */
    protected $logger;

    /** @var TranslatorInterface  */
    protected $translator;

    /** @var  int */
    protected $counter = 0;

    /** @var string */
    protected $successMessage = 'oro.user.password.reset.mass_action.success';

    /** @var string */
    protected $errorMessage = 'oro.user.password.reset.mass_action.failure';

    public function __construct(
        Processor $mailerProcessor,
        UserManager $userManager,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->mailerProcessor = $mailerProcessor;
        $this->userManager = $userManager;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $massActionOptions = $args->getMassAction()->getOptions();

        /** @var IterableResult $results */
        $results = $args->getResults();
        $results->rewind();

        $this->em = $results->getSource()->getEntityManager();

        $results->setPageCallback(function () {
            $this->em->flush();
            $this->em->clear();
            $this->counter = 0;
        });

        while ($results->current() != null) {
            $entity = $results->current()->getRootEntity();

            if (!$entity instanceof User) {
                // hydration failed
                $responseMessage = $massActionOptions->offsetGetByPath('[messages][success]', $this->errorMessage);
                return new MassActionResponse(
                    false,
                    $this->translator->trans($responseMessage)
                );
            }

            if (null === $entity->getConfirmationToken()) {
                $entity->setConfirmationToken($entity->generateToken());
            }

            try {
                $this->mailerProcessor->sendForcedResetPasswordAsAdminEmail($entity);
            } catch (\Exception $e) {
                if (null !== $this->logger) {
                    $this->logger->error(sprintf('Sending email to %s failed.', $entity->getEmail()));
                }
            }

            $entity->setLoginDisabled(true);
            $this->userManager->updateUser($entity, false);
            $this->counter++;
            $results->next();
        }

        if ($this->counter > 0) {
            $this->em->flush();
            $this->em->clear();
        }

        $responseMessage = $massActionOptions->offsetGetByPath('[messages][success]', $this->successMessage);

        return new MassActionResponse(
            true,
            $this->translator->trans($responseMessage)
        );
    }
}
