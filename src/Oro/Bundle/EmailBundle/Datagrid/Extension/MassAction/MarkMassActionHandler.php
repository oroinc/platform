<?php

namespace Oro\Bundle\EmailBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponseInterface;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Mass action handler that marks mails as seen.
 */
class MarkMassActionHandler implements MassActionHandlerInterface
{
    public const int MARK_READ = 1;
    public const int MARK_UNREAD = 2;
    public const int  FLUSH_BATCH_SIZE = 100;

    protected array $needToProcessThreadIds = [];
    protected string $responseMessage = 'oro.email.datagrid.mark.success_message';

    public function __construct(
        protected ManagerRegistry $doctrine,
        protected TranslatorInterface $translator,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected TokenAccessorInterface $tokenAccessor,
        protected EmailManager $emailManager
    ) {
    }

    #[\Override]
    public function handle(MassActionHandlerArgs $args): MassActionResponseInterface
    {
        $data = $args->getData();

        $massAction = $args->getMassAction();
        $options = $massAction->getOptions()->toArray();
        $entityManager = $this->getEntityManager();
        $entityManager->beginTransaction();
        try {
            set_time_limit(0);
            $iteration = $this->handleHeadEmails($options, $data);
            $this->handleThreadEmails($options);
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }

        return $this->getResponse($args, $iteration);
    }

    /**
     * @param array $options
     * @param array $data
     * @return int
     */
    protected function handleHeadEmails($options, $data)
    {
        $markType = $options['mark_type'];
        $folderType = null;
        $isAllSelected = $this->isAllSelected($data);
        $iteration = 0;
        $emailUserIds = [];

        if (array_key_exists('values', $data) && !empty($data['values'])) {
            $emailUserIds = explode(',', $data['values']);
        }

        if ($emailUserIds || $isAllSelected) {
            if (isset($data['filters']['folder']['value'])) {
                $folderType = $data['filters']['folder']['value'];
            }

            $queryBuilder = $this->getEntityManager()->getRepository(EmailUser::class)
                ->getEmailUserBuilderForMassAction(
                    $emailUserIds,
                    $this->tokenAccessor->getUser(),
                    $folderType,
                    $isAllSelected,
                    $this->tokenAccessor->getOrganization()
                );
            $iteration = $this->process($queryBuilder, $markType, $iteration);
        }

        return $iteration;
    }

    /**
     * @param array $options
     */
    protected function handleThreadEmails($options)
    {
        $iteration = 0;
        $markType = $options['mark_type'];

        if (!$this->needToProcessThreadIds) {
            return;
        }

        $entityManager = $this->getEntityManager();
        $queryBuilder = $entityManager->getRepository(EmailUser::class)
            ->getEmailUserByThreadId($this->needToProcessThreadIds, $this->tokenAccessor->getUser());

        $result = $queryBuilder->getQuery()->iterate();
        foreach ($result as $entity) {
            if ($this->authorizationChecker->isGranted('EDIT', $entity)) {
                $this->emailManager->setEmailUserSeen($entity, $markType === self::MARK_READ);
            }
            $entityManager->persist($entity);

            if (($iteration % self::FLUSH_BATCH_SIZE) === 0) {
                $entityManager->flush();
                $entityManager->clear();
            }
            $iteration++;
        }
        $entityManager->flush();
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function isAllSelected($data)
    {
        return array_key_exists('inset', $data) && $data['inset'] === '0';
    }

    /**
     * @param MassActionHandlerArgs $args
     * @param int $entitiesCount
     *
     * @return MassActionResponse
     */
    protected function getResponse(MassActionHandlerArgs $args, $entitiesCount = 0)
    {
        $massAction = $args->getMassAction();
        $responseMessage = (string) $massAction->getOptions()
            ->offsetGetByPath('[messages][success]', $this->responseMessage);

        $successful = $entitiesCount > 0;
        $options = ['count' => $entitiesCount];

        return new MassActionResponse(
            $successful,
            $this->translator->trans(
                $responseMessage,
                ['%count%' => $entitiesCount]
            ),
            $options
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $markType
     * @param int $iteration
     * @return mixed
     */
    protected function process($queryBuilder, $markType, $iteration)
    {
        $entityManager = $this->getEntityManager();
        $result = $queryBuilder->getQuery()->toIterable();
        foreach ($result as $entity) {
            /** @var EmailUser $entity */
            if ($this->authorizationChecker->isGranted('EDIT', $entity)) {
                $this->emailManager->setEmailUserSeen($entity, $markType === self::MARK_READ);
            }

            if ($entity->getEmail()->getThread()) {
                $this->needToProcessThreadIds[] = $entity->getEmail()->getThread()->getId();
            }

            $entityManager->persist($entity);

            if (($iteration % self::FLUSH_BATCH_SIZE) === 0) {
                $entityManager->flush();
                $entityManager->clear();
            }
            $iteration++;
        }
        $entityManager->flush();

        return $iteration;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManager();
    }
}
