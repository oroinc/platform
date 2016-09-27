<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\LoginHistory;
use Oro\Bundle\UserBundle\Entity\Repository\LoginHistoryRepository;

class LoginHistoryManager
{
    /** @var Registry */
    protected $doctrine;

    public function __construct(Registry $registry)
    {
        $this->doctrine = $registry;
    }

    public function addLoginFailure(UserInterface $user, $providerClass)
    {
        /** @var LoginHistoryRepository $repository */
        $repository = $this->getLoginHistoryRepository();
        $loginHistory = $repository->getByUserAndProviderClass($user, $providerClass);

        if (!$loginHistory) {
            $loginHistory = $this->createLoginHistory($user, $providerClass);
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $diff = $now->diff($loginHistory->getUpdatedAt());

        if (intval($diff->format('%h')) > 24) {
            $loginHistory->setFailedDailyAttempts(0);
        }
        // increase failures
        $loginHistory->increaseFailedAttempts();
        $loginHistory->increaseFailedDailyAttempts();

        // save changes
        $em = $this->doctrine->getManagerForClass('OroUserBundle:LoginHistory');
        $em->persist($loginHistory);
        $em->flush();
    }

    /**
     * @param UserInterface $user
     * @param $providerClass
     * @return LoginHistory|void
     */
    public function createLoginHistory(UserInterface $user, $providerClass)
    {
        if (!$user || !$providerClass) {
            return null;
        }

        $entity = new LoginHistory($user, $providerClass);
        $entity->setUser($user);
        $entity->setProviderClass($providerClass);

        $em = $this->doctrine->getManagerForClass('OroUserBundle:LoginHistory');
        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    /**
     * @return LoginHistoryRepository
     */
    protected function getLoginHistoryRepository()
    {
        return $this->doctrine->getRepository('OroUserBundle:LoginHistory');
    }
}
