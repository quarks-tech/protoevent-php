<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\SymfonyBundle\Command;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use QuarksTech\ProtoEvent\EventBus\Subscriber;
use QuarksTech\ProtoEvent\Transport\ReceiverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

/**
 * Command to consume events from a ProtoEvent queue.
 *
 * Usage: bin/console protoevent:consume <queue-name>
 */

class ConsumeCommand extends Command
{
    protected static $defaultName = 'protoevent:consume';

    private ContainerInterface $container;
    private LoggerInterface $logger;
    /** @var string[] */
    private array $queueNames;

    /**
     * @param string[] $queueNames
     */
    public function __construct(ContainerInterface $container, array $queueNames, ?LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->queueNames = $queueNames;
        $this->logger = $logger ?? new NullLogger();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Consume events from a queue')
            ->addArgument('queue', InputArgument::REQUIRED, 'Queue name to consume from')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command consumes events from a specified queue.

  <info>php %command.full_name% monolith.v2.subscriptions</info>
  <info>php %command.full_name% notifications</info>

Only handlers tagged for the specified queue are registered. Tag handlers with:

  tags:
    - { name: 'protoevent.handler', queue: 'my-queue' }

Handlers without a queue attribute are registered to all queues.
HELP,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName = $input->getArgument('queue');

        if (!in_array($queueName, $this->queueNames, true)) {
            $this->logger->error('Unknown queue "{queue}". Available queues: {available}', [
                'queue' => $queueName,
                'available' => implode(', ', $this->queueNames),
            ]);
            return 1;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $this->container->get("protoevent.{$queueName}.subscriber");
        /** @var ReceiverInterface $receiver */
        $receiver = $this->container->get("protoevent.{$queueName}.receiver");

        $this->logger->info('Starting consumer for queue "{queue}"', ['queue' => $queueName]);

        $serviceInfos = $subscriber->getServiceInfos();

        if (empty($serviceInfos)) {
            $this->logger->warning('No event handlers registered for queue "{queue}". Nothing to consume.', [
                'queue' => $queueName,
            ]);
            return 0;
        }

        foreach ($serviceInfos as $serviceInfo) {
            $this->logger->info('Registered handler: {service} for events: {events}', [
                'service' => $serviceInfo->getServiceName(),
                'events' => implode(', ', $serviceInfo->getEvents()),
            ]);
        }

        $this->logger->info('Consumer started for queue "{queue}"', ['queue' => $queueName]);

        try {
            $subscriber->subscribe($receiver);
        } catch (Throwable $e) {
            $this->logger->error('Consumer error: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            return 1;
        }

        $this->logger->info('Consumer stopped gracefully');

        return 0;
    }
}
