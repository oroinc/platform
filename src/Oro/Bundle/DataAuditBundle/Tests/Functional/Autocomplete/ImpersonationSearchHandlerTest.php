<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\DataAuditBundle\Autocomplete\ImpersonationSearchHandler;
use Oro\Bundle\DataAuditBundle\Model\EntityReference;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Bundle\UserBundle\Tests\Functional\Helper\AdminUserTrait;

/**
 * @dbIsolationPerTest
 */
class ImpersonationSearchHandlerTest extends WebTestCase
{
    use AdminUserTrait;

    /** @var ImpersonationSearchHandler */
    private $searchHandler;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EntityChangesToAuditEntryConverter */
    private $entityChangesToAuditEntryConverter;

    protected function setUp()
    {
        $this->initClient();

        $this->searchHandler = $this->getContainer()->get('oro_dataaudit.autocomplete.impersonation_search_handler');
        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->entityChangesToAuditEntryConverter = $this->getContainer()->get(
            'oro_dataaudit.converter.entity_changes_to_audit_entry'
        );
    }

    public function testSearchWithoutAudit()
    {
        $this->assertSame(
            ['results' => [], 'more' => false],
            $this->searchHandler->search('', 1, 25, true)
        );

        $this->assertSame(
            ['results' => [], 'more' => false],
            $this->searchHandler->search('', 1, 25, false)
        );

        $impersonation = new Impersonation();
        $impersonation->setUser($user = $this->getAdminUser());

        $this->saveImpresonation($impersonation);

        $this->assertSame(
            ['results' => [], 'more' => false],
            $this->searchHandler->search($impersonation->getId(), 1, 25, true)
        );

        $this->assertSame(
            ['results' => [], 'more' => false],
            $this->searchHandler->search($impersonation->getIpAddress(), 1, 25, false)
        );
    }

    public function testSearchById()
    {
        $impersonation = new Impersonation();
        $impersonation->setUser($user = $this->getAdminUser());
        $this->createImpersonationAndAuditLog($impersonation);

        $this->assertSame(
            [
                'results' => [
                    [
                        'id' => $impersonation->getId(),
                        'ipAddress' => $impersonation->getIpAddress(),
                        'token' => $impersonation->getToken(),
                        'ipAddressToken' => sprintf(
                            'Impersonated from %s using %s',
                            $impersonation->getIpAddress(),
                            $impersonation->getToken()
                        ),
                    ],
                ],
                'more' => false,
            ],
            $this->searchHandler->search($impersonation->getId(), 1, 25, true)
        );
    }

    public function testSearchByIds()
    {
        $impersonation = new Impersonation();
        $impersonation->setUser($user = $this->getAdminUser());
        $this->createImpersonationAndAuditLog($impersonation);

        $impersonation2 = new Impersonation();
        $impersonation2->setUser($user = $this->getAdminUser());
        $this->createImpersonationAndAuditLog($impersonation2);


        $this->assertSame(
            [
                'results' => [
                    [
                        'id' => $impersonation2->getId(),
                        'ipAddress' => $impersonation2->getIpAddress(),
                        'token' => $impersonation2->getToken(),
                        'ipAddressToken' => sprintf(
                            'Impersonated from %s using %s',
                            $impersonation2->getIpAddress(),
                            $impersonation2->getToken()
                        ),
                    ],
                    [
                        'id' => $impersonation->getId(),
                        'ipAddress' => $impersonation->getIpAddress(),
                        'token' => $impersonation->getToken(),
                        'ipAddressToken' => sprintf(
                            'Impersonated from %s using %s',
                            $impersonation->getIpAddress(),
                            $impersonation->getToken()
                        ),
                    ],
                ],
                'more' => false,
            ],
            $this->searchHandler->search(implode(',', [$impersonation->getId(), $impersonation2->getId()]), 1, 25, true)
        );
    }

    public function testSearchByQueryByToken()
    {
        $impersonation = new Impersonation();
        $impersonation->setUser($user = $this->getAdminUser());
        $this->createImpersonationAndAuditLog($impersonation);

        $this->assertSame(
            [
                'results' => [
                    [
                        'id' => $impersonation->getId(),
                        'ipAddress' => $impersonation->getIpAddress(),
                        'token' => $impersonation->getToken(),
                        'ipAddressToken' => sprintf(
                            'Impersonated from %s using %s',
                            $impersonation->getIpAddress(),
                            $impersonation->getToken()
                        ),
                    ],
                ],
                'more' => false,
            ],
            $this->searchHandler->search(substr($impersonation->getToken(), 5, 5), 1, 25)
        );
    }

    public function testSearchByQueryByIpAddress()
    {
        $impersonation = new Impersonation();
        $impersonation->setUser($user = $this->getAdminUser());
        $this->createImpersonationAndAuditLog($impersonation);

        $this->assertSame(
            [
                'results' => [
                    [
                        'id' => $impersonation->getId(),
                        'ipAddress' => $impersonation->getIpAddress(),
                        'token' => $impersonation->getToken(),
                        'ipAddressToken' => sprintf(
                            'Impersonated from %s using %s',
                            $impersonation->getIpAddress(),
                            $impersonation->getToken()
                        ),
                    ],
                ],
                'more' => false,
            ],
            $this->searchHandler->search(substr($impersonation->getIpAddress(), 0, 3), 1, 25)
        );
    }

    public function testSearchByWithEmptyQuery()
    {
        $impersonation = new Impersonation();
        $impersonation->setUser($user = $this->getAdminUser());
        $this->createImpersonationAndAuditLog($impersonation);

        $impersonation2 = new Impersonation();
        $impersonation2->setUser($user = $this->getAdminUser());
        $this->createImpersonationAndAuditLog($impersonation2);


        $this->assertSame(
            [
                'results' => [
                    [
                        'id' => $impersonation2->getId(),
                        'ipAddress' => $impersonation2->getIpAddress(),
                        'token' => $impersonation2->getToken(),
                        'ipAddressToken' => sprintf(
                            'Impersonated from %s using %s',
                            $impersonation2->getIpAddress(),
                            $impersonation2->getToken()
                        ),
                    ],
                    [
                        'id' => $impersonation->getId(),
                        'ipAddress' => $impersonation->getIpAddress(),
                        'token' => $impersonation->getToken(),
                        'ipAddressToken' => sprintf(
                            'Impersonated from %s using %s',
                            $impersonation->getIpAddress(),
                            $impersonation->getToken()
                        ),
                    ],
                ],
                'more' => false,
            ],
            $this->searchHandler->search('', 1, 25)
        );
    }

    public function testPagination()
    {
        $impersonation = new Impersonation();
        $impersonation->setUser($user = $this->getAdminUser());
        $this->createImpersonationAndAuditLog($impersonation);

        $impersonation2 = new Impersonation();
        $impersonation2->setUser($user = $this->getAdminUser());
        $this->createImpersonationAndAuditLog($impersonation2);

        $this->assertSame(
            [
                'results' => [
                    [
                        'id' => $impersonation2->getId(),
                        'ipAddress' => $impersonation2->getIpAddress(),
                        'token' => $impersonation2->getToken(),
                        'ipAddressToken' => sprintf(
                            'Impersonated from %s using %s',
                            $impersonation2->getIpAddress(),
                            $impersonation2->getToken()
                        ),
                    ],
                ],
                'more' => false,
            ],
            $this->searchHandler->search('', 1, 1)
        );

        $this->assertSame(
            [
                'results' => [
                    [
                        'id' => $impersonation->getId(),
                        'ipAddress' => $impersonation->getIpAddress(),
                        'token' => $impersonation->getToken(),
                        'ipAddressToken' => sprintf(
                            'Impersonated from %s using %s',
                            $impersonation->getIpAddress(),
                            $impersonation->getToken()
                        ),
                    ],
                ],
                'more' => false,
            ],
            $this->searchHandler->search('', 2, 1)
        );
    }

    /**
     * @param Impersonation $impersonation
     */
    private function createImpersonationAndAuditLog(Impersonation $impersonation): void
    {
        $this->saveImpresonation($impersonation);

        $this->entityChangesToAuditEntryConverter->convert(
            [
                [
                    'entity_class' => get_class($this->getAdminUser()),
                    'entity_id' => $this->getAdminUser()->getId(),
                    'change_set' => ['namePrefix' => [null, 'MR']],
                ],
            ],
            UUIDGenerator::v4(),
            new \DateTime(),
            new EntityReference(get_class($this->getAdminUser()), $this->getAdminUser()->getId()),
            new EntityReference(
                get_class($this->getAdminUser()->getOrganization()),
                $this->getAdminUser()->getOrganization()->getId()
            ),
            new EntityReference(Impersonation::class, $impersonation->getId())
        );
    }

    /**
     * @param Impersonation $impersonation
     */
    private function saveImpresonation(Impersonation $impersonation): void
    {
        $this->doctrineHelper->getEntityManager($impersonation)->persist($impersonation);
        $this->doctrineHelper->getEntityManager($impersonation)->flush($impersonation);
        $this->doctrineHelper->getEntityManager($impersonation)->refresh($impersonation);
    }
}
