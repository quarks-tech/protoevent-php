<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\SymfonyBundle\EventHandler;

use QuarksTech\ProtoEvent\EventBus\EventHandler;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that auto-discovers event handlers.
 *
 * Any class implementing a generated handler interface is automatically registered:
 *
 *     class SubscriptionCreatedHandler implements SubscriptionCreatedEventHandler
 *     {
 *         public function handleSubscriptionCreatedEvent(EventContext $ctx, SubscriptionCreatedEvent $event): void
 *         {
 *             // ...
 *         }
 *     }
 *
 * The ServiceDesc and event name are derived from the interface naming convention:
 *   - Interface: {Namespace}\EventBus\Handler\{EventName}EventHandler
 *   - ServiceDesc: {Namespace}\EventBus\ServiceDesc
 *   - Event name: {EventName}
 */
class EventHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('quarks_tech.proto_event.subscriber')) {
            return;
        }

        $subscriberDefinition = $container->getDefinition('quarks_tech.proto_event.subscriber');

        foreach ($container->getDefinitions() as $serviceId => $definition) {
            $class = $definition->getClass();
            if ($class === null || !class_exists($class)) {
                continue;
            }

            // Skip internal services
            if (str_starts_with($serviceId, 'quarks_tech.proto_event.')) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            // Check if class implements EventHandler
            if (!$reflection->implementsInterface(EventHandler::class)) {
                continue;
            }

            $this->registerHandler($container, $subscriberDefinition, $serviceId, $reflection);
        }
    }

    private function registerHandler(
        ContainerBuilder $container,
        Definition $subscriberDefinition,
        string $serviceId,
        ReflectionClass $reflection,
    ): void {
        foreach ($reflection->getInterfaces() as $interface) {
            if ($interface->getName() === EventHandler::class) {
                continue;
            }

            if (!$interface->implementsInterface(EventHandler::class)) {
                continue;
            }

            $metadata = $this->deriveMetadataFromInterface($interface->getName());
            if ($metadata === null) {
                continue;
            }

            [$serviceDescClass, $eventName] = $metadata;

            $subscriberDefinition->addMethodCall('registerEventHandler', [
                $this->createServiceDescReference($container, $serviceDescClass),
                $eventName,
                new Reference($serviceId),
            ]);
        }
    }

    /**
     * Derives ServiceDesc class and event name from handler interface naming convention.
     *
     * @return array{string, string}|null [serviceDescClass, eventName] or null if convention not followed
     */
    private function deriveMetadataFromInterface(string $interfaceName): ?array
    {
        $reflection = new ReflectionClass($interfaceName);
        $namespace = $reflection->getNamespaceName();
        $shortName = $reflection->getShortName();

        if (!str_ends_with($namespace, '\\Handler')) {
            return null;
        }

        if (!str_ends_with($shortName, 'EventHandler')) {
            return null;
        }

        $eventBusNamespace = substr($namespace, 0, -8);
        $serviceDescClass = $eventBusNamespace . '\\ServiceDesc';

        if (!class_exists($serviceDescClass)) {
            return null;
        }

        $eventName = substr($shortName, 0, -12);

        return [$serviceDescClass, $eventName];
    }

    private function createServiceDescReference(ContainerBuilder $container, string $serviceDescClass): Reference
    {
        $serviceId = 'quarks_tech.proto_event.service_desc.' . md5($serviceDescClass);

        if (!$container->hasDefinition($serviceId)) {
            $container->register($serviceId)
                ->setFactory([$serviceDescClass, 'get'])
                ->setPublic(false);
        }

        return new Reference($serviceId);
    }
}
