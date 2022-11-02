<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitSelectAutocomplete;
use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitType;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\ParentBusinessUnitValidator;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessUnitTypeTest extends FormIntegrationTestCase
{
    private const NAME = 'Sample Name';

    /** @var BusinessUnitType */
    private $form;

    protected function setUp(): void
    {
        parent::setUp();

        $businessUnitManager = $this->createMock(BusinessUnitManager::class);
        $businessUnitManager->expects($this->any())
            ->method('getBusinessUnitsTree')
            ->willReturn([]);

        $businessUnitManager->expects($this->any())
            ->method('getBusinessUnitIds')
            ->willReturn([]);

        $this->form = new BusinessUnitType($businessUnitManager, $this->createMock(TokenAccessorInterface::class));
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager = $this->createMock(EntityManager::class));

        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata = new ClassMetadata(User::class));
        $classMetadata->setIdentifier(['id']);

        $searchRegistry = $this->createMock(SearchRegistry::class);
        $searchRegistry->expects($this->any())
            ->method('getSearchHandler')
            ->willReturn($handler = $this->createMock(SearchHandlerInterface::class));

        $handler->expects($this->any())
            ->method('getProperties')
            ->willReturn([]);

        $handler->expects($this->any())
            ->method('getEntityName')
            ->willReturn(BusinessUnit::class);

        return [
            new PreloadedExtension([
                BusinessUnitType::class => new BusinessUnitType(
                    $this->createMock(BusinessUnitManager::class),
                    $this->createMock(TokenAccessorInterface::class)
                ),
                BusinessUnitSelectAutocomplete::class => new BusinessUnitSelectAutocomplete(
                    $entityManager,
                    BusinessUnit::class,
                    $this->createMock(BusinessUnitManager::class)
                ),
                EntityIdentifierType::class => new EntityIdentifierType($registry),
                OroJquerySelect2HiddenType::class => new OroJquerySelect2HiddenType(
                    $entityManager,
                    $searchRegistry,
                    $this->createMock(ConfigProvider::class)
                ),
            ], []),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators(): array
    {
        return [
            'parent_business_unit_validator' => $this->createMock(ParentBusinessUnitValidator::class),
        ];
    }

    public function testConfigureOptions()
    {
        $optionResolver = $this->createMock(OptionsResolver::class);
        $optionResolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => BusinessUnit::class, 'ownership_disabled' => true]);

        $this->form->configureOptions($optionResolver);
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->any())
            ->method('add')
            ->willReturnSelf();

        $this->form->buildForm($builder, []);
    }

    /**
     * @dataProvider submitWhenInvalidWebsiteDataProvider
     */
    public function testSubmitWhenInvalidWebsite(string $website): void
    {
        $form = $this->factory->create(BusinessUnitType::class, new BusinessUnit());

        $form->submit(
            [
                'name' => self::NAME,
                'website' => $website,
            ]
        );

        $expectedBusinessUnit = (new BusinessUnit())
            ->setName(self::NAME)
            ->setWebsite($website);

        $this->assertFormIsNotValid($form);
        $this->assertEquals($expectedBusinessUnit, $form->getData());
    }

    public function submitWhenInvalidWebsiteDataProvider(): array
    {
        return [
            ['website' => 'sample-string'],
            ['website' => 'unsupported-protocol://sample-site'],
            ['website' => 'javascript:alert(1)'],
            ['website' => 'jAvAsCrIpt:alert(1)'],
        ];
    }
}
