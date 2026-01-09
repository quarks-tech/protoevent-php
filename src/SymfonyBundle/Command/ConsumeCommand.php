<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\SymfonyBundle\Command;

use QuarksTech\ProtoEvent\EventBus\Subscriber;
use QuarksTech\ProtoEvent\Transport\ReceiverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

class ConsumeCommand extends Command
{
    protected static $defaultName = 'protoevent:consume';
    protected static $defaultDescription = 'Consume events from a queue';

    private ContainerInterface $container;
    /** @var string[] */
    private array $queueNames;

    /**
     * @param string[] $queueNames
     */
    public function __construct(ContainerInterface $container, array $queueNames)
    {
        $this->container = $container;
        $this->queueNames = $queueNames;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
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
        $io = new SymfonyStyle($input, $output);
        $queueName = $input->getArgument('queue');

        if (!in_array($queueName, $this->queueNames, true)) {
            $io->error(sprintf(
                'Unknown queue "%s". Available queues: %s',
                $queueName,
                implode(', ', $this->queueNames),
            ));
            return Command::FAILURE;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $this->container->get("protoevent.{$queueName}.subscriber");
        /** @var ReceiverInterface $receiver */
        $receiver = $this->container->get("protoevent.{$queueName}.receiver");

        $io->info(sprintf('Starting consumer for queue "%s"...', $queueName));

        $serviceInfos = $subscriber->getServiceInfos();

        if (empty($serviceInfos)) {
            $io->warning('No event handlers registered. Nothing to consume.');
            return Command::SUCCESS;
        }

        $io->writeln('Registered handlers:');
        foreach ($serviceInfos as $serviceInfo) {
            $io->writeln(sprintf(
                '  %s: %s',
                $serviceInfo->getServiceName(),
                implode(', ', $serviceInfo->getEvents()),
            ));
        }

        $io->success('Consumer started. Press Ctrl+C to stop.');

        try {
            $subscriber->subscribe($receiver);
        } catch (Throwable $e) {
            $io->error('Consumer error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->info('Consumer stopped gracefully.');

        return Command::SUCCESS;
    }
}
