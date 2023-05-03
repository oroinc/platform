<?php

namespace Oro\Bundle\UserBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Handler\ResetPasswordHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handler for reset password request.
 */
class ResetPasswordActionHandler implements MassActionHandlerInterface
{
    const SUCCESS_MESSAGE = 'oro.user.password.force_reset.mass_action.success';
    const ERROR_MESSAGE = 'oro.user.password.force_reset.mass_action.failure';

    /** @var ResetPasswordHandler */
    protected $resetPasswordHandler;

    /** @var TranslatorInterface  */
    protected $translator;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function __construct(
        ResetPasswordHandler $resetPasswordHandler,
        TranslatorInterface $translator,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->resetPasswordHandler = $resetPasswordHandler;
        $this->translator = $translator;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $currentUser = $this->tokenAccessor->getUser();
        $currentUserId = $currentUser ? $currentUser->getId() : null;

        $count = 0;
        /** @var IterableResult $results */
        $results = $args->getResults();

        for ($results->rewind(); $result = $results->current(); $results->next()) {
            $user = $result->getRootEntity();

            if (!$user instanceof User) {
                // hydration failed
                continue;
            }

            // current user should not be able to reset own password
            if ($currentUserId === $user->getId()) {
                continue;
            }

            $count += $this->disableLoginAndNotify($user);
        }

        $results->getSource()->getEntityManager()->flush();

        return $this->generateResponse($count);
    }

    /**
     * @param User $user
     *
     * @return int Processed count
     */
    protected function disableLoginAndNotify(User $user)
    {
        return $this->resetPasswordHandler->resetPasswordAndNotify($user) ? 1 : 0;
    }

    /**
     * @param int $count Processed entries
     *
     * @return MassActionResponse
     */
    protected function generateResponse($count)
    {
        if ($count > 0) {
            return new MassActionResponse(true, $this->translator->trans(self::SUCCESS_MESSAGE, ['%count%' => $count]));
        }

        return new MassActionResponse(false, $this->translator->trans(self::ERROR_MESSAGE, ['%count%' => $count]));
    }
}
