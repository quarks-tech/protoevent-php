<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\SymfonyBundle\DependencyInjection;

use QuarksTech\ProtoEvent\Encoding\CodecRegistry;
use QuarksTech\ProtoEvent\EventBus\Publisher;
use QuarksTech\ProtoEvent\EventBus\Subscriber;
use QuarksTech\ProtoEvent\SymfonyBundle\Command\ConsumeCommand;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpConnection;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpReceiver;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpReceiverOptions;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpSender;
use QuarksTech\ProtoEvent\Transport\Amqp\AmqpSenderOptions;
use QuarksTech\ProtoEvent\Transport\Amqp\ParkingLotReceiver;
use QuarksTech\ProtoEvent\Transport\Amqp\ParkingLotReceiverOptions;
use QuarksTech\ProtoEvent\Transport\GracefulShutdownInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class QuarksTechProtoEventExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Register codec registry
        $codecRegistryDef = new Definition(CodecRegistry::class);
        $container->setDefinition('quarks_tech.proto_event.codec_registry', $codecRegistryDef);
        $container->setAlias(CodecRegistry::class, 'quarks_tech.proto_event.codec_registry');

        // Register connection
        $this->registerConnection($container, $config['connection'] ?? []);

        // Register publisher
        $this->registerPublisher($container, $config['publisher'] ?? []);

        // Register single subscriber (handlers are added by EventHandlerPass)
        $this->registerSubscriber($container);

        // Register receivers for each queue
        $queueNames = [];
        foreach ($config['queues'] ?? [] as $queueName => $queueConfig) {
            $this->registerQueueReceiver($container, $queueName, $queueConfig);
            $queueNames[] = $queueName;
        }

        // Store queue names for ConsumeCommand
        $container->setParameter('quarks_tech.proto_event.queue_names', $queueNames);

        // Register console command
        $this->registerCommand($container, $queueNames);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerConnection(ContainerBuilder $container, array $config): void
    {
        $definition = new Definition(AmqpConnection::class);

        if (!empty($config['dsn'])) {
            $definition->setFactory([AmqpConnection::class, 'fromDsn']);
            $definition->setArguments([$config['dsn']]);
        } else {
            $definition->setArguments([
                $config['host'] ?? 'localhost',
                $config['port'] ?? 5672,
                $config['user'] ?? 'guest',
                $config['password'] ?? 'guest',
                $config['vhost'] ?? '/',
            ]);
        }

        $container->setDefinition('quarks_tech.proto_event.connection', $definition);
        $container->setAlias(AmqpConnection::class, 'quarks_tech.proto_event.connection');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerPublisher(ContainerBuilder $container, array $config): void
    {
        $optionsDef = new Definition(AmqpSenderOptions::class);
        $optionsDef->setArguments([
            $config['delivery_mode'] ?? 2,
            $config['setup_topology'] ?? false,
        ]);
        $container->setDefinition('quarks_tech.proto_event.sender_options', $optionsDef);

        $senderDef = new Definition(AmqpSender::class);
        $senderDef->setArguments([
            new Reference('quarks_tech.proto_event.connection'),
            new Reference('quarks_tech.proto_event.sender_options'),
        ]);
        $container->setDefinition('quarks_tech.proto_event.sender', $senderDef);

        $publisherDef = new Definition(Publisher::class);
        $publisherDef->setArguments([
            new Reference('quarks_tech.proto_event.sender'),
        ]);
        $container->setDefinition('quarks_tech.proto_event.publisher', $publisherDef);
        $container->setAlias(Publisher::class, 'quarks_tech.proto_event.publisher');
    }

    private function registerSubscriber(ContainerBuilder $container): void
    {
        $subscriberDef = new Definition(Subscriber::class);
        $subscriberDef->setArguments([
            'protoevent', // Name is not important - we use queue name for actual queue
            new Reference('quarks_tech.proto_event.codec_registry'),
        ]);
        $subscriberDef->setPublic(true);
        $container->setDefinition('quarks_tech.proto_event.subscriber', $subscriberDef);
        $container->setAlias(Subscriber::class, 'quarks_tech.proto_event.subscriber');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerQueueReceiver(ContainerBuilder $container, string $queueName, array $config): void
    {
        $useParkingLot = $config['parking_lot'] ?? false;

        if ($useParkingLot) {
            $optionsDef = new Definition(ParkingLotReceiverOptions::class);
            $optionsDef->setArguments([
                $queueName,
                $config['prefetch_count'] ?? 3,
                $config['max_retries'] ?? 3,
                $config['retry_backoff_ms'] ?? 15000,
                $config['setup_topology'] ?? false,
                $config['setup_bindings'] ?? false,
            ]);
            $container->setDefinition("quarks_tech.proto_event.queue.{$queueName}.receiver_options", $optionsDef);

            $receiverDef = new Definition(ParkingLotReceiver::class);
        } else {
            $optionsDef = new Definition(AmqpReceiverOptions::class);
            $optionsDef->setArguments([
                $queueName,
                $config['prefetch_count'] ?? 3,
                $config['requeue_on_error'] ?? false,
                $config['setup_topology'] ?? false,
                $config['setup_bindings'] ?? false,
            ]);
            $container->setDefinition("quarks_tech.proto_event.queue.{$queueName}.receiver_options", $optionsDef);

            $receiverDef = new Definition(AmqpReceiver::class);
        }

        $receiverDef->setArguments([
            new Reference('quarks_tech.proto_event.connection'),
            new Reference("quarks_tech.proto_event.queue.{$queueName}.receiver_options"),
            null, // marshaler (use default)
            new Reference('logger', ContainerBuilder::NULL_ON_INVALID_REFERENCE),
            new Reference(GracefulShutdownInterface::class, ContainerBuilder::NULL_ON_INVALID_REFERENCE),
        ]);
        $receiverDef->setPublic(true);
        $container->setDefinition("quarks_tech.proto_event.queue.{$queueName}.receiver", $receiverDef);
    }

    /**
     * @param string[] $queueNames
     */
    private function registerCommand(ContainerBuilder $container, array $queueNames): void
    {
        $commandDef = new Definition(ConsumeCommand::class);
        $commandDef->setArguments([
            new Reference('service_container'),
            $queueNames,
        ]);
        $commandDef->addTag('console.command');
        $container->setDefinition('quarks_tech.proto_event.command.consume', $commandDef);
    }
}
