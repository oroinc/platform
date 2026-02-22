<?php

namespace Oro\Bundle\ReminderBundle\Twig;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Bundle\ReminderBundle\Model\WebSocket\MessageParamsProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve reminders data for the current user:
 *   - oro_reminder_get_requested_reminders_data
 */
class ReminderExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_reminder_get_requested_reminders_data', [$this, 'getRequestedRemindersData'])
        ];
    }

    public function getRequestedRemindersData(): array
    {
        $user = $this->getTokenStorage()->getToken()?->getUser();
        if (!$user instanceof User) {
            return [];
        }

        return $this->getMessageParamsProvider()->getMessageParamsForReminders(
            $this->getReminderRepository()->findRequestedReminders($user)
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            MessageParamsProvider::class,
            TokenStorageInterface::class,
            ManagerRegistry::class
        ];
    }

    private function getMessageParamsProvider(): MessageParamsProvider
    {
        return $this->container->get(MessageParamsProvider::class);
    }

    private function getTokenStorage(): TokenStorageInterface
    {
        return $this->container->get(TokenStorageInterface::class);
    }

    private function getReminderRepository(): ReminderRepository
    {
        return $this->container->get(ManagerRegistry::class)->getRepository(Reminder::class);
    }
}
