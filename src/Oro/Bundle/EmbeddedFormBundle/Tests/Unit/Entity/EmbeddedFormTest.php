<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Entity;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use PHPUnit\Framework\TestCase;

class EmbeddedFormTest extends TestCase
{
    private EmbeddedForm $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new EmbeddedForm();
    }

    /**
     * @dataProvider getSetDataProvider
     */
    public function testGetSet($property, $value, $expected): void
    {
        if ($value !== null) {
            call_user_func([$this->entity, 'set' . ucfirst($property)], $value);
        }
        $this->assertEquals($expected, call_user_func_array([$this->entity, 'get' . ucfirst($property)], []));
    }

    public function getSetDataProvider(): array
    {
        $organization = $this->createMock(Organization::class);

        return [
            'owner' => ['owner', $organization, $organization],
        ];
    }

    public function testShouldSetEntityPropertiesAndReturnBack(): void
    {
        $formType = 'Test\Type';
        $css = 'test styles';
        $title = 'test title';
        $successMessage = 'test success message';

        $formEntity = new EmbeddedForm();
        $formEntity->setFormType($formType);
        $formEntity->setCss($css);
        $formEntity->setTitle($title);
        $formEntity->setSuccessMessage($successMessage);

        $this->assertEquals($formType, $formEntity->getFormType());
        $this->assertEquals($css, $formEntity->getCss());
        $this->assertEquals($title, $formEntity->getTitle());
        $this->assertEquals($successMessage, $formEntity->getSuccessMessage());
    }
}
