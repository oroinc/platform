<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Functional\ImportExport\EventListener;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\ImportExport\EventListener\FileStrategyEventListener;
use Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures\LoadUsersAvatarsDigitalAssets;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/**
 * @dbIsolationPerTest
 */
class DigitalAssetAwareFileStrategyEventListenerTest extends WebTestCase
{
    use EntityTrait;

    /** @var FileStrategyEventListener|\PHPUnit\Framework\MockObject\MockObject */
    private $fileStrategyListener;

    /** @var DigitalAssetAwareFileStrategyEventListenerTest */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadUsersAvatarsDigitalAssets::class]);

        $this->fileStrategyListener = $this->getContainer()
            ->get('oro_attachment.import_export.event_listener.file_strategy');

        $this->listener = $this->getContainer()
            ->get('oro_digital_asset.import_export.event_listener.digital_asset_aware_file_strategy_event_listener');
    }

    public function testWhenNoFileFields(): void
    {
        $context = $this->getContext();
        $event = $this->getEvent($context, new Role());

        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
    }

    private function getContext(array $itemData = []): Context
    {
        $context = new Context([]);
        $context->setValue('itemData', $itemData);

        return $context;
    }

    private function getEvent(Context $context, object $entity): StrategyEvent
    {
        return new StrategyEvent(
            $this->getContainer()->get('oro_user.importexport.strategy.user.add_or_replace'),
            $entity,
            $context
        );
    }

    public function testWhenDamNotEnabled(): void
    {
        $this->toggleDam(false);

        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

        $file = new File();
        $symfonyFile = new SymfonyFile(
            (string) $this->getContainer()->get('kernel')
                ->locateResource('@OroUserBundle/Tests/Functional/DataFixtures/files/empty.jpg')
        );
        $file->setFile($symfonyFile);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        $this->fileStrategyListener->onProcessAfter($event);
        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
        $this->assertNotEmpty($existingUser->getAvatar()->getFile());
        $this->assertNotTrue($existingUser->getAvatar()->isEmptyFile());
        $this->assertEmpty($existingUser->getAvatar()->getDigitalAsset());
    }

    public function testWhenFieldExcluded(): void
    {
        $this->toggleDam(true);

        $entityConfigManager = $this->getContainer()->get('oro_entity_config.config_manager');
        $fieldConfig = $entityConfigManager->getFieldConfig('importexport', User::class, 'avatar');
        $fieldConfig->set('excluded', true);

        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

        $file = new File();
        $symfonyFile = new SymfonyFile(
            (string) $this->getContainer()->get('kernel')
                ->locateResource('@OroUserBundle/Tests/Functional/DataFixtures/files/empty.jpg')
        );
        $file->setFile($symfonyFile);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        $this->fileStrategyListener->onProcessAfter($event);
        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
        $this->assertNotEmpty($existingUser->getAvatar()->getFile());
        $this->assertNotTrue($existingUser->getAvatar()->isEmptyFile());
        $this->assertEmpty($existingUser->getAvatar()->getDigitalAsset());
    }

    public function testWhenFileWhenReuseFromDigitalAssetSource(): void
    {
        $this->setToken($this->getReference('user'));

        $this->toggleDam(true);

        $fieldName = 'avatar';
        $digitalAsset = $this->getReference('user_2_avatar_digital_asset');
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);
        $sourceFileUuid = $digitalAsset->getSourceFile()->getUuid();

        $file = new File();
        $file->setUuid($sourceFileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $sourceFileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->assertNull($existingUser->getAvatar());

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        $this->fileStrategyListener->onProcessAfter($event);

        $this->assertNotNull($existingUser->getAvatar());
        $this->assertNull($existingUser->getAvatar()->getDigitalAsset());

        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
        $this->assertNotNull($existingUser->getAvatar());
        $this->assertNotNull($existingUser->getAvatar()->getDigitalAsset());

        $preFlushEventArgs = new PreFlushEventArgs($this->getContainer()->get('doctrine')->getManager());
        $this->listener->preFlush($preFlushEventArgs);

        $this->assertSame($digitalAsset, $existingUser->getAvatar()->getDigitalAsset());
    }

    private function toggleDam(bool $isEnabled): void
    {
        $entityConfigManager = $this->getContainer()->get('oro_entity_config.config_manager');
        $avatarFieldConfig = $entityConfigManager->getFieldConfig('attachment', User::class, 'avatar');
        $avatarFieldConfig->set('use_dam', $isEnabled);
        $entityConfigManager->persist($avatarFieldConfig);
        $entityConfigManager->flush();
    }

    public function testWhenFileWhenReuseFromDigitalAssetChild(): void
    {
        $this->setToken($this->getReference('user'));

        $this->toggleDam(true);

        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);
        $user2 = $this->getReference('user2');
        $sourceFileUuid = $user2->getAvatar()->getUuid();
        $digitalAsset = $user2->getAvatar()->getDigitalAsset();

        $file = new File();
        $file->setUuid($sourceFileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $sourceFileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar(new File());
        $event->setEntity($existingUser);

        $this->fileStrategyListener->onProcessAfter($event);

        $this->assertNotNull($existingUser->getAvatar());
        $this->assertNull($existingUser->getAvatar()->getDigitalAsset());

        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
        $this->assertNotNull($existingUser->getAvatar());
        $this->assertNotNull($existingUser->getAvatar()->getDigitalAsset());

        $preFlushEventArgs = new PreFlushEventArgs($this->getContainer()->get('doctrine')->getManager());
        $this->listener->preFlush($preFlushEventArgs);

        $this->assertSame($digitalAsset, $existingUser->getAvatar()->getDigitalAsset());
    }

    public function testWhenFileWhenUploadDigitalAsset(): void
    {
        $this->toggleDam(true);

        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

        $file = new File();
        $symfonyFile = new SymfonyFile(
            __DIR__ . '/../../../../../UserBundle/Tests/Functional/DataFixtures/files/empty.jpg'
        );
        $file->setFile($symfonyFile);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => '']]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        $this->assertNotNull($existingUser->getAvatar());
        $this->assertNull($existingUser->getAvatar()->getDigitalAsset());

        $this->fileStrategyListener->onProcessAfter($event);

        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
        $this->assertNotNull($existingUser->getAvatar());
        $this->assertNotNull($existingUser->getAvatar()->getDigitalAsset());

        $preFlushEventArgs = new PreFlushEventArgs($this->getContainer()->get('doctrine')->getManager());
        $this->listener->preFlush($preFlushEventArgs);

        $this->assertNotNull($existingUser->getAvatar()->getDigitalAsset());
    }

    public function testWhenFileWhenCannotReuseOrUpload(): void
    {
        $this->toggleDam(true);

        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

        $file = new File();
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => '']]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        $this->assertNotNull($existingUser->getAvatar());
        $this->assertNull($existingUser->getAvatar()->getDigitalAsset());

        $this->listener->onProcessAfter($event);

        $preFlushEventArgs = new PreFlushEventArgs($this->getContainer()->get('doctrine')->getManager());
        $this->listener->preFlush($preFlushEventArgs);

        $this->assertNotNull($existingUser->getAvatar());
        $this->assertNull($existingUser->getAvatar()->getDigitalAsset());

        $this->assertEquals(
            [
                'Error in row #0. Failed to reuse digital asset: file not found by specified UUID. Failed to create '
                . 'new digital asset: there is no file specified for uploading',
                'Error in row #0. Digital Asset importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );
    }

    public function testWhenFileWhenCannotFindDigitalAsset(): void
    {
        $this->toggleDam(true);

        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);
        $fileWithInvalidDigitalAsset = $this->getReference('file_with_invalid_digital_asset');
        $fileUuid = $fileWithInvalidDigitalAsset->getUuid();

        $file = new File();
        $file->setUuid($fileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $fileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        $this->assertNotNull($existingUser->getAvatar());
        $this->assertNull($existingUser->getAvatar()->getDigitalAsset());

        $this->listener->onProcessAfter($event);

        $preFlushEventArgs = new PreFlushEventArgs($this->getContainer()->get('doctrine')->getManager());
        $this->listener->preFlush($preFlushEventArgs);

        $this->assertNotNull($existingUser->getAvatar());
        $this->assertNull($existingUser->getAvatar()->getDigitalAsset());

        $this->assertEquals(
            [
                'Error in row #0. Cannot find digital asset #999999 specified as parent for file with'
                . ' UUID 07bad972-48c9-4ba9-8cb3-eb595ab2d069',
                'Error in row #0. Failed to reuse digital asset: file not found by specified UUID.'
                .' Failed to create new digital asset: there is no file specified for uploading',
                'Error in row #0. Digital Asset importing has failed, entity is skipped'
            ],
            $context->getErrors()
        );
    }

    private function setToken(User $user): void
    {
        $token = new UsernamePasswordOrganizationToken(
            $user,
            self::AUTH_PW,
            'main',
            $this->getReference('organization')
        );
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
    }
}
