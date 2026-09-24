<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\NavigationBundle\EventListener\ExcludedEntitiesConfigRequestListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ExcludedEntitiesConfigRequestListenerTest extends TestCase
{
    private const EXCLUDED_ENTITY = 'Acme\Bundle\AcmeBundle\Entity\Excluded';

    /**
     * @dataProvider entityConfigRouteDataProvider
     */
    public function testOnKernelControllerArgumentsForExcludedEntity(string $route): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage(
            \sprintf('The "%s" entity is not available in the entity management.', self::EXCLUDED_ENTITY)
        );

        $this->getListener()->onKernelControllerArguments(
            $this->getEvent([new EntityConfigModel(self::EXCLUDED_ENTITY)], $route)
        );
    }

    public function entityConfigRouteDataProvider(): array
    {
        return [
            'entity config' => ['oro_entityconfig_view'],
            'entity extend' => ['oro_entityextend_field_create'],
            'attribute' => ['oro_attribute_update'],
        ];
    }

    public function testOnKernelControllerArgumentsForFieldOfExcludedEntity(): void
    {
        $fieldConfigModel = new FieldConfigModel('sampleField', 'string');
        $fieldConfigModel->setEntity(new EntityConfigModel(self::EXCLUDED_ENTITY));

        $this->expectException(NotFoundHttpException::class);

        $this->getListener()->onKernelControllerArguments($this->getEvent([$fieldConfigModel]));
    }

    public function testOnKernelControllerArgumentsForAnotherEntity(): void
    {
        $this->expectNotToPerformAssertions();

        $fieldConfigModel = new FieldConfigModel('sampleField', 'string');
        $fieldConfigModel->setEntity(new EntityConfigModel('Acme\Bundle\AcmeBundle\Entity\Another'));

        $this->getListener()->onKernelControllerArguments($this->getEvent([
            new EntityConfigModel('Acme\Bundle\AcmeBundle\Entity\Another'),
            $fieldConfigModel,
            new FieldConfigModel('sampleField', 'string'),
            new Request(),
        ]));
    }

    public function testOnKernelControllerArgumentsForAnotherRoute(): void
    {
        $this->expectNotToPerformAssertions();

        $this->getListener()->onKernelControllerArguments(
            $this->getEvent([new EntityConfigModel(self::EXCLUDED_ENTITY)], 'oro_navigation_global_menu_index')
        );
    }

    public function testOnKernelControllerArgumentsWhenNoEntityIsExcluded(): void
    {
        $this->expectNotToPerformAssertions();

        (new ExcludedEntitiesConfigRequestListener([]))->onKernelControllerArguments(
            $this->getEvent([new EntityConfigModel(self::EXCLUDED_ENTITY)])
        );
    }

    private function getListener(): ExcludedEntitiesConfigRequestListener
    {
        return new ExcludedEntitiesConfigRequestListener([self::EXCLUDED_ENTITY]);
    }

    private function getEvent(array $arguments, string $route = 'oro_entityconfig_update'): ControllerArgumentsEvent
    {
        $request = new Request();
        $request->attributes->set('_route', $route);

        return new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
            static fn () => null,
            $arguments,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}
