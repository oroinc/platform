<?php

namespace Oro\Bundle\UserBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Handler that enables user list.
 */
class UsersEnableSwitchActionHandler implements MassActionHandlerInterface
{
    const FLUSH_BATCH_SIZE = 100;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var bool */
    protected $isEnabled;

    /** @var string */
    protected $successMessage;

    /** @var string */
    protected $errorMessage;

    /** @var User|null */
    protected $currentUser;

    /**
     * @param AclHelper             $aclHelper
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface   $translator
     * @param bool                  $isEnabled
     * @param string                $successMessage
     * @param string                $errorMessage
     */
    public function __construct(
        AclHelper $aclHelper,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        $isEnabled,
        $successMessage,
        $errorMessage
    ) {
        $this->aclHelper      = $aclHelper;
        $this->tokenStorage   = $tokenStorage;
        $this->translator     = $translator;
        $this->isEnabled      = $isEnabled;
        $this->successMessage = $successMessage;
        $this->errorMessage   = $errorMessage;
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * {@inheritdoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $token = $this->tokenStorage->getToken();
        $count = 0;
        if ($token && $this->currentUser = $token->getUser()) {
            set_time_limit(0);
            $results = $args->getResults();
            /** @var Query $query */
            $query   = $results->getSource();
            $this->aclHelper->apply($query, 'EDIT');
            $em = $results->getSource()->getEntityManager();

            $processedEntities = [];
            foreach ($results as $result) {
                if ($this->processUser($result)) {
                    $count++;
                    $processedEntities[] = $result->getRootEntity();
                }
                if ($count % self::FLUSH_BATCH_SIZE === 0) {
                    $this->finishBatch($em, $processedEntities);
                    $processedEntities = [];
                }
            }

            $this->finishBatch($em, $processedEntities);
        }
        $this->currentUser = null;

        return $count > 0
            ? new MassActionResponse(true, $this->translator->trans($this->successMessage, ['%count%' => $count]))
            : new MassActionResponse(false, $this->translator->trans($this->errorMessage, ['%count%' => $count]));
    }

    /**
     * @param ResultRecord $result
     *
     * @return bool
     */
    protected function processUser(ResultRecord $result)
    {
        $user = $result->getRootEntity();
        if (!$user instanceof User) {
            return false;//unexpected result record
        }
        if ($user->getId() === $this->currentUser->getId()) {
            return false;//disable operation on current user
        }
        if ($user->isEnabled() === $this->isEnabled) {
            return false;//do not count not affected records
        }
        $user->setEnabled($this->isEnabled);

        return true;
    }

    /**
     * @param EntityManager $em
     * @param User[] $processedEntities
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function finishBatch(EntityManager $em, $processedEntities)
    {
        foreach ($processedEntities as $entity) {
            $em->flush($entity);
            $em->detach($entity);
        }
    }
}
