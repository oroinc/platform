<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertInterface;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Represents a notification alert item for email resource type.
 */
class EmailSyncNotificationAlert implements NotificationAlertInterface
{
    public const SOURCE_TYPE = 'EmailSync';
    public const RESOURCE_TYPE = 'email';

    public const OPERATION_IMPORT = 'import';
    public const OPERATION_IMPORT_BODY = 'import body';

    public const ALERT_TYPE_AUTH = 'auth';
    public const ALERT_TYPE_REFRESH_TOKEN = 'refresh token';
    public const ALERT_TYPE_SWITCH_FOLDER = 'switch folder';
    public const ALERT_TYPE_SYNC = 'sync';

    public const STEP_GET = 'get';
    public const STEP_GET_LIST = 'get list';
    public const STEP_CONVERT = 'convert';

    protected string $id;
    protected string $alertType;
    protected ?string $operation = null;
    protected ?string $step = null;
    protected ?int $itemId = null;
    protected ?string $externalId = null;
    protected ?string $message = null;

    protected ?int $userId = null;
    protected int $organizationId = 0;
    protected int $emailOriginId = 0;

    protected array $additionalInfo = [];

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceType(): string
    {
        return self::SOURCE_TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $data = [
            NotificationAlertManager::ID              => $this->id,
            NotificationAlertManager::SOURCE_TYPE     => self::SOURCE_TYPE,
            NotificationAlertManager::RESOURCE_TYPE   => self::RESOURCE_TYPE,
            NotificationAlertManager::ALERT_TYPE      => $this->alertType,
            NotificationAlertManager::OPERATION       => $this->operation,
            NotificationAlertManager::USER            => $this->userId,
            NotificationAlertManager::ORGANIZATION    => $this->organizationId,
            NotificationAlertManager::ADDITIONAL_INFO => array_merge(
                $this->additionalInfo,
                ['emailOriginId' => $this->emailOriginId]
            )
        ];

        if (null !== $this->message) {
            $data[NotificationAlertManager::MESSAGE] = $this->message;
        }

        if (null !== $this->step) {
            $data[NotificationAlertManager::STEP] = $this->step;
        }

        if (null !== $this->itemId) {
            $data[NotificationAlertManager::ITEM_ID] = $this->itemId;
        }

        if (null !== $this->externalId) {
            $data[NotificationAlertManager::EXTERNAL_ID] = $this->externalId;
        }

        return $data;
    }

    /**
     * This class must be instantiated via factory methods.
     */
    private function __construct()
    {
    }

    public function getAlertType(): string
    {
        return $this->alertType;
    }

    public function getStep(): ?string
    {
        return $this->step;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function setOrganizationId(int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function setEmailOriginId(int $emailOriginId): void
    {
        $this->emailOriginId = $emailOriginId;
    }

    /**
     * Creates a notification alert object that represents a failure during authentication.
     */
    public static function createForRefreshTokenFail(?string $message = null): EmailSyncNotificationAlert
    {
        $item = new EmailSyncNotificationAlert();
        $item->alertType = self::ALERT_TYPE_REFRESH_TOKEN;
        $item->operation = self::OPERATION_IMPORT;
        $item->id = UUIDGenerator::v4();
        $item->message = $message;

        return $item;
    }

    /**
     * Creates a notification alert object that represents a failure during authentication.
     */
    public static function createForAuthFail(?string $message = null): EmailSyncNotificationAlert
    {
        $item = new EmailSyncNotificationAlert();
        $item->alertType = self::ALERT_TYPE_AUTH;
        $item->operation = self::OPERATION_IMPORT;
        $item->id = UUIDGenerator::v4();
        $item->message = $message;

        return $item;
    }

    /**
     * Creates a notification alert object that represents a failure during getting the collection of emails.
     */
    public static function createForGetListFail(
        ?string $message = null,
        array   $additionalInfo = []
    ): EmailSyncNotificationAlert {
        $item = new EmailSyncNotificationAlert();
        $item->alertType = self::ALERT_TYPE_SYNC;
        $item->operation = self::OPERATION_IMPORT;
        $item->step = self::STEP_GET_LIST;
        $item->id = UUIDGenerator::v4();
        $item->message = $message;
        $item->additionalInfo = $additionalInfo;

        return $item;
    }

    /**
     * Creates a notification alert object that represents a failure during switching the email folder.
     */
    public static function createForSwitchFolderFail(
        ?string $message = null,
        ?string $folderName = null,
        ?int    $folderId = null
    ): EmailSyncNotificationAlert {
        $item = new EmailSyncNotificationAlert();
        $item->alertType = self::ALERT_TYPE_SWITCH_FOLDER;
        $item->operation = self::OPERATION_IMPORT;
        $item->id = UUIDGenerator::v4();
        $item->message = $message;

        $item->additionalInfo['folderName'] = $folderName;
        $item->additionalInfo['folderId'] = $folderId;

        return $item;
    }

    /**
     * Creates a notification alert object that represents a failure during getting the email body data.
     */
    public static function createForGetItemBodyFail(
        int     $itemId,
        ?string $message = null
    ): EmailSyncNotificationAlert {
        $item = new EmailSyncNotificationAlert();
        $item->alertType = self::ALERT_TYPE_SYNC;
        $item->operation = self::OPERATION_IMPORT_BODY;
        $item->step = self::STEP_GET;
        $item->id = UUIDGenerator::v4();
        $item->message = $message;
        $item->additionalInfo['emailId'] = $itemId;

        return $item;
    }

    /**
     * Creates a notification alert object that represents a failure during save the email body data.
     */
    public static function createForSaveItemBodyFail(
        int     $itemId,
        ?string $message = null
    ): EmailSyncNotificationAlert {
        $item = new EmailSyncNotificationAlert();
        $item->alertType = self::ALERT_TYPE_SYNC;
        $item->operation = self::OPERATION_IMPORT_BODY;
        $item->step = self::STEP_CONVERT;
        $item->id = UUIDGenerator::v4();
        $item->message = $message;
        $item->additionalInfo['emailId'] = $itemId;

        return $item;
    }

    /**
     * Creates a notification alert object that represents a failure during converting email data to the email object.
     */
    public static function createForConvertFailed(
        ?string $message = null,
        array   $additionalInfo = []
    ): EmailSyncNotificationAlert {
        $item = new EmailSyncNotificationAlert();
        $item->alertType = self::ALERT_TYPE_SYNC;
        $item->operation = self::OPERATION_IMPORT;
        $item->step = self::STEP_CONVERT;
        $item->id = UUIDGenerator::v4();
        $item->message = $message;
        $item->additionalInfo = $additionalInfo;

        return $item;
    }
}
