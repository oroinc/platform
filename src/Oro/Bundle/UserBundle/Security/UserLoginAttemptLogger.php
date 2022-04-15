<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Logs the success and failed login attempts to the default logger as well as to the DB.
 */
class UserLoginAttemptLogger
{
    private const DEFAULT_SOURCE_CODE = 1;

    private ManagerRegistry $doctrine;
    private UserLoggingInfoProviderInterface $userInfoProvider;
    private TranslatorInterface $translator;
    private LoggerInterface $logger;
    private string $attemptClass;
    private array $loginSources;

    private const DB_TYPES = [
        'id'         => 'guid',
        'success'    => 'boolean',
        'source'     => 'integer',
        'username'   => 'text',
        'attempt_at' => 'datetime',
        'context'    => 'json',
        'ip'         => 'text',
        'user_agent' => 'text',
        'user_id'    => 'integer'
    ];

    public function __construct(
        ManagerRegistry                  $doctrine,
        UserLoggingInfoProviderInterface $userInfoProvider,
        TranslatorInterface              $translator,
        LoggerInterface                  $logger,
        string                           $attemptClass,
        array                            $loginSources
    ) {
        $this->doctrine = $doctrine;
        $this->userInfoProvider = $userInfoProvider;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->attemptClass = $attemptClass;
        $this->loginSources = $loginSources;
    }

    /**
     * Logs success user login attempt.
     */
    public function logSuccessLoginAttempt(mixed $user, string $source, array $additionalContext = []): void
    {
        $this->doLogAttempt(true, $user, $source, $additionalContext);
    }

    /**
     * Logs failed user login attempt.
     */
    public function logFailedLoginAttempt(mixed $user, string $source, array $additionalContext = []): void
    {
        $this->doLogAttempt(false, $user, $source, $additionalContext);
    }

    /**
     * Returns a list of configured login sources can be used as the choice list in forms.
     */
    public function getSourceChoices(): array
    {
        $result = [];
        foreach ($this->loginSources as $loginSource) {
            $result[$this->translator->trans($loginSource['label'])] = $loginSource['code'];
        }

        return $result;
    }

    private function doLogAttempt(
        bool   $success,
        mixed  $user,
        string $source,
        array  $additionalContext = []
    ): void {
        $sourceCode = isset($this->loginSources[$source])
            ? $this->loginSources[$source]['code']
            : self::DEFAULT_SOURCE_CODE;
        $userInfo = $this->userInfoProvider->getUserLoggingInfo($user);
        $fullContext = array_merge($userInfo, $additionalContext);

        $data = [
            'id'         => UUIDGenerator::v4(),
            'success'    => $success,
            'source'     => $sourceCode,
            'username'   => \is_object($user) ? $user->getUserName() : $user,
            'attempt_at' => new \DateTime('now', new \DateTimeZone('UTC')),
            'context'    => $fullContext
        ];

        if (\is_object($user)) {
            $data['user_id'] = $user->getId();
        }
        if (isset($data['context']['ipaddress'])) {
            $data['ip'] = $data['context']['ipaddress'];
            unset($data['context']['ipaddress']);
        }
        if (isset($data['context']['user agent'])) {
            $data['user_agent'] = $data['context']['user agent'];
            unset($data['context']['user agent']);
        }

        $this->logItemToDb($data);
        $this->logger->notice(
            $success ? 'Success login attempt.' : 'Failed login attempt.',
            $data
        );
    }

    private function logItemToDb(array $fields): void
    {
        $types = [];
        foreach (array_keys($fields) as $fieldName) {
            $types[$fieldName] = self::DB_TYPES[$fieldName];
        }

        try {
            $em = $this->getEntityManager();
            $em->getConnection()->insert($this->getEntityMetadata($em)->getTableName(), $fields, $types);
        } catch (\Exception $e) {
            $this->logger->error('Cannot save user attempt log item.', ['exception' => $e]);
        }
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass($this->attemptClass);
    }

    private function getEntityMetadata(EntityManagerInterface $em): ClassMetadata
    {
        return $em->getClassMetadata($this->attemptClass);
    }
}
