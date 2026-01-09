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
 * Compiler pass that registers tagged event handlers to queue-specific subscribers.
 *
 * Services tagged with 'protoevent.handler' are automatically registered.
 * Use the 'queue' attribute to bind a handler to a specific queue:
 *
 *     services:
 *         App\Handler\MyEventHandler:
 *             tags:
 *                 - { name: 'protoevent.handler', queue: 'my-queue' }
 *
 * Handlers without a queue attribute are registered to all configured queues.
 *
 * The ServiceDesc and event name are derived from the interface naming convention:
 *   - Interface: {Namespace}\EventBus\Handler\{EventName}EventHandler
 *   - ServiceDesc: {Namespace}\EventBus\ServiceDesc
 *   - Event name: {EventName}
 */
class EventHandlerPass implements CompilerPassInterface
{
    public const TAG_NAME = 'protoevent.handler';

    public function process(ContainerBuilder $container): void
    {
        /** @var string[] $queueNames */
        $queueNames = $container->getParameter('protoevent.queue_names');

        // Group handlers by queue
        /** @var array<string, array{serviceId: string, reflection: \ReflectionClass}[]> $handlersByQueue */
        $handlersByQueue = [];

        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);
            $class = $definition->getClass();

            if ($class === null) {
                continue;
            }

            $reflection = $container->getReflectionClass($class);
            if ($reflection === null) {
                continue;
            }

            // Collect queue names from all tags on this service
            $queues = [];
            foreach ($tags as $tag) {
                if (isset($tag['queue'])) {
                    $queues[] = $tag['queue'];
                }
            }

            // If no queue specified, register to all queues
            if (empty($queues)) {
                $queues = $queueNames;
            }

            foreach ($queues as $queue) {
                $handlersByQueue[$queue][] = [
                    'serviceId' => $serviceId,
                    'reflection' => $reflection,
                ];
            }
        }

        // Create a subscriber for each queue with its handlers
        foreach ($queueNames as $queueName) {
            $subscriberId = "protoevent.{$queueName}.subscriber";

            if (!$container->hasDefinition($subscriberId)) {
                continue;
            }

            $subscriberDefinition = $container->getDefinition($subscriberId);

            foreach ($handlersByQueue[$queueName] ?? [] as $handler) {
                $this->registerHandler(
                    $container,
                    $subscriberDefinition,
                    $handler['serviceId'],
                    $handler['reflection'],
                );
            }
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
        $serviceId = 'protoevent.service_desc.' . md5($serviceDescClass);

        if (!$container->hasDefinition($serviceId)) {
            $container->register($serviceId)
                ->setFactory([$serviceDescClass, 'get'])
                ->setPublic(false);
        }

        return new Reference($serviceId);
    }
}
