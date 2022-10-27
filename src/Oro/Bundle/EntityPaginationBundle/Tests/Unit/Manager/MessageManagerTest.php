<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Bundle\EntityPaginationBundle\Manager\MessageManager;
use Oro\Bundle\EntityPaginationBundle\Navigation\EntityPaginationNavigation;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Session */
    private $session;

    /** @var EntityPaginationNavigation|\PHPUnit\Framework\MockObject\MockObject */
    private $navigation;

    /** @var EntityPaginationStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var MessageManager */
    private $manager;

    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
        $this->navigation = $this->createMock(EntityPaginationNavigation::class);
        $this->storage = $this->createMock(EntityPaginationStorage::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id, array $parameters = []) {
                return str_replace(array_keys($parameters), array_values($parameters), $id . '.trans');
            });

        $this->manager = new MessageManager($this->session, $translator, $this->navigation, $this->storage);
    }

    public function testAddFlashMessage()
    {
        $type = 'test_type';
        $message = 'Test Message';

        $this->assertEmpty($this->session->getFlashBag()->all());
        $this->manager->addFlashMessage($type, $message);
        $this->assertEquals([$type => [$message]], $this->session->getFlashBag()->all());
    }

    /**
     * @dataProvider getNotAvailableMessageDataProvider
     */
    public function testGetNotAvailableMessage(string $expected, string $scope, int $count = null)
    {
        $entity = new \stdClass();

        $this->navigation->expects($this->once())
            ->method('getTotalCount')
            ->with($entity, $scope)
            ->willReturn($count);

        $this->assertEquals($expected, $this->manager->getNotAvailableMessage($entity, $scope));
    }

    public function getNotAvailableMessageDataProvider(): array
    {
        return [
            'no count' => [
                'expected' => 'oro.entity_pagination.message.not_available.trans',
                'scope' => EntityPaginationManager::VIEW_SCOPE,
            ],
            'view with count' => [
                'expected' =>
                    'oro.entity_pagination.message.not_available.trans ' .
                    'oro.entity_pagination.message.stats_number_view_12_record|stats_number_view_12_records.trans',
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'count' => 12,
            ],
            'edit with count' => [
                'expected' =>
                    'oro.entity_pagination.message.not_available.trans ' .
                    'oro.entity_pagination.message.stats_number_edit_23_record|stats_number_edit_23_records.trans',
                'scope' => EntityPaginationManager::EDIT_SCOPE,
                'count' => 23,
            ],
        ];
    }

    public function testGetStatsMessageForInvalidScope()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Scope "invalid" is not available.');

        $entity = new \stdClass();
        $scope = 'invalid';

        $this->navigation->expects($this->once())
            ->method('getTotalCount')
            ->with($entity, $scope)
            ->willReturn(1);

        $this->manager->getNotAvailableMessage($entity, $scope);
    }

    /**
     * @dataProvider getNotAccessibleMessageDataProvider
     */
    public function testGetNotAccessibleMessage(string $expected, string $scope, int $count = null)
    {
        $entity = new \stdClass();

        $this->navigation->expects($this->once())
            ->method('getTotalCount')
            ->with($entity, $scope)
            ->willReturn($count);

        $this->assertEquals($expected, $this->manager->getNotAccessibleMessage($entity, $scope));
    }

    public function getNotAccessibleMessageDataProvider(): array
    {
        return [
            'no count' => [
                'expected' => 'oro.entity_pagination.message.not_accessible.trans',
                'scope' => EntityPaginationManager::VIEW_SCOPE,
            ],
            'view with count' => [
                'expected' =>
                    'oro.entity_pagination.message.not_accessible.trans ' .
                    'oro.entity_pagination.message.stats_number_view_12_record|stats_number_view_12_records.trans',
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'count' => 12,
            ],
            'edit with count' => [
                'expected' =>
                    'oro.entity_pagination.message.not_accessible.trans ' .
                    'oro.entity_pagination.message.stats_number_edit_23_record|stats_number_edit_23_records.trans',
                'scope' => EntityPaginationManager::EDIT_SCOPE,
                'count' => 23,
            ],
        ];
    }

    /**
     * @dataProvider getInfoMessageDataProvider
     */
    public function testGetInfoMessage(
        ?string $expected,
        string $scope,
        bool $shown,
        int $viewCount = null,
        int $editCount = null
    ) {
        $entity = new \stdClass();
        $entityName = get_class($entity);

        $this->storage->expects($this->once())
            ->method('isInfoMessageShown')
            ->with($entityName, $scope)
            ->willReturn($shown);

        $this->navigation->expects($this->any())
            ->method('getTotalCount')
            ->with($entity, $this->isType('string'))
            ->willReturnMap([
                [$entity, EntityPaginationManager::VIEW_SCOPE, $viewCount],
                [$entity, EntityPaginationManager::EDIT_SCOPE, $editCount],
            ]);

        if ($expected) {
            $this->storage->expects($this->once())
                ->method('setInfoMessageShown')
                ->with($entityName, $scope);
        } else {
            $this->storage->expects($this->never())
                ->method('setInfoMessageShown');
        }

        $this->assertSame($expected, $this->manager->getInfoMessage($entity, $scope));
    }

    public function getInfoMessageDataProvider(): array
    {
        return [
            'message already shown' => [
                'expected' => null,
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'shown' => true,
            ],
            'no entities is storage' => [
                'expected' => null,
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'shown' => false,
                'viewCount' => null
            ],
            'only view' => [
                'expected' => null,
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'shown' => false,
                'viewCount' => 10
            ],
            'only edit' => [
                'expected' => null,
                'scope' => EntityPaginationManager::EDIT_SCOPE,
                'shown' => false,
                'viewCount' => null,
                'editCount' => 10,
            ],
            'view to edit equals view scope' => [
                'expected' => null,
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'shown' => false,
                'viewCount' => 10,
                'editCount' => 10,
            ],
            'view to edit equals edit scope' => [
                'expected' => null,
                'scope' => EntityPaginationManager::EDIT_SCOPE,
                'shown' => false,
                'viewCount' => 10,
                'editCount' => 10,
            ],
            'edit to view increased' => [
                'expected' =>
                    'oro.entity_pagination.message.stats_number_view_5_record|stats_number_view_5_records.trans',
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'shown' => false,
                'viewCount' => 5,
                'editCount' => 10,
            ],
            'edit to view decreased' => [
                'expected' =>
                    'oro.entity_pagination.message.stats_number_view_10_record|stats_number_view_10_records.trans',
                'scope' => EntityPaginationManager::VIEW_SCOPE,
                'shown' => false,
                'viewCount' => 10,
                'editCount' => 5,
            ],
            'view to edit increased' => [
                'expected' =>
                    'oro.entity_pagination.message.stats_number_edit_10_record|stats_number_edit_10_records.trans',
                'scope' => EntityPaginationManager::EDIT_SCOPE,
                'shown' => false,
                'viewCount' => 5,
                'editCount' => 10,
            ],
            'view to edit decreased' => [
                'expected' =>
                    'oro.entity_pagination.message.stats_changed_view_to_edit.trans ' .
                    'oro.entity_pagination.message.stats_number_edit_5_record|stats_number_edit_5_records.trans',
                'scope' => EntityPaginationManager::EDIT_SCOPE,
                'shown' => false,
                'viewCount' => 10,
                'editCount' => 5,
            ],
        ];
    }
}
