<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\MultipleEntityType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MultipleEntityTypeTest extends FormIntegrationTestCase
{
    private const PERMISSION_ALLOW = 'test_permission_allow';
    private const PERMISSION_DISALLOW = 'test_permission_disallow';

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $metadata = new ClassMetadataInfo(\stdClass::class);
        $metadata->identifier[] = 'id';

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturnMap([[\stdClass::class, $metadata]]);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([[\stdClass::class, $em]]);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                new MultipleEntityType($this->doctrineHelper, $this->authorizationChecker),
                new EntityIdentifierType($this->registry)
            ], [])
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(MultipleEntityType::class, null, ['class' => \stdClass::class]);

        $this->assertTrue($form->has('added'));
        $this->assertTrue($form->has('removed'));
    }

    public function testHasKnownOptions()
    {
        $form = $this->factory->create(MultipleEntityType::class, null, ['class' => \stdClass::class]);

        $knownOptions = [
            'add_acl_resource',
            'class',
            'default_element',
            'initial_elements',
            'selector_window_title',
            'extra_config',
            'selection_url',
            'selection_url_method',
            'selection_route',
            'selection_route_parameters',
        ];

        foreach ($knownOptions as $option) {
            $this->assertTrue($form->getConfig()->hasOption($option));
        }
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testViewHasVars(array $options, string $expectedKey, mixed $expectedValue)
    {
        $form = $this->factory->create(
            MultipleEntityType::class,
            null,
            array_merge($options, ['class' => \stdClass::class])
        );

        if (isset($options['add_acl_resource'])) {
            $this->authorizationChecker->expects($this->once())
                ->method('isGranted')
                ->with($options['add_acl_resource'])
                ->willReturn($expectedValue);
        } else {
            $this->authorizationChecker->expects($this->never())
                ->method('isGranted');
        }

        $view = $form->createView();
        $this->assertArrayHasKey($expectedKey, $view->vars);
        $this->assertEquals($expectedValue, $view->vars[$expectedKey]);
    }

    public function optionsDataProvider(): array
    {
        return [
            [
                [],
                'allow_action',
                true
            ],
            [
                ['add_acl_resource' => self::PERMISSION_ALLOW],
                'allow_action',
                true
            ],
            [
                ['add_acl_resource' => self::PERMISSION_DISALLOW],
                'allow_action',
                false
            ],
            [
                ['initial_elements' => []],
                'initial_elements',
                []
            ],
            [
                [],
                'initial_elements',
                null
            ],
            [
                ['selector_window_title' => 'Select'],
                'selector_window_title',
                'Select'
            ],
            [
                [],
                'selector_window_title',
                null
            ],
            [
                ['default_element' => 'name'],
                'default_element',
                'name'
            ],
            [
                [],
                'default_element',
                null
            ],
            [
                ['selection_url' => 'testUrlSelection'],
                'selection_url',
                'testUrlSelection',
            ],
            [
                ['selection_route' => 'testRoute'],
                'selection_route',
                'testRoute',
            ],
            [
                ['selection_route_parameters' => ['testParam1']],
                'selection_route_parameters',
                ['testParam1'],
            ]
        ];
    }
}
