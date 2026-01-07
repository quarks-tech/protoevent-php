<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\EventBus;

use QuarksTech\ProtoEvent\Encoding\CodecRegistry;
use QuarksTech\ProtoEvent\Event\Metadata;
use QuarksTech\ProtoEvent\Exception\EncodingException;
use QuarksTech\ProtoEvent\Exception\UnprocessableEventException;
use QuarksTech\ProtoEvent\Transport\ReceiverInterface;
use QuarksTech\ProtoEvent\Transport\SetuperInterface;
use RuntimeException;
use InvalidArgumentException;

/**
 * Event subscriber that routes events to registered handlers
 */
final class Subscriber implements EventHandlerRegistrar
{
    private string $name;
    private bool $isServing = false;
    /** @var array<string, array<string, EventHandlerInfo>> */
    private array $services = [];
    /** @var SubscriberInterceptor[] */
    private array $interceptors = [];
    private CodecRegistry $codecRegistry;

    public function __construct(string $name, ?CodecRegistry $codecRegistry = null)
    {
        $this->name = $name;
        $this->codecRegistry = $codecRegistry ?? new CodecRegistry();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function registerEventHandler(
        ServiceDesc $serviceDesc,
        string $eventName,
        EventHandler $handler,
    ): void {
        if ($this->isServing) {
            throw new RuntimeException(
                'Cannot register event handler after subscriber has started',
            );
        }

        $eventDesc = $serviceDesc->getEventDesc($eventName);
        if ($eventDesc === null) {
            throw new InvalidArgumentException(
                "Event '{$eventName}' not found in service descriptor",
            );
        }

        // Verify handler implements the required interface
        $handlerInterface = $eventDesc->getHandlerInterface();
        if (!$handler instanceof $handlerInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    "Handler must implement %s",
                    $handlerInterface,
                ),
            );
        }

        $serviceName = $serviceDesc->getServiceName();

        if (!isset($this->services[$serviceName])) {
            $this->services[$serviceName] = [];
        }

        if (isset($this->services[$serviceName][$eventName])) {
            throw new RuntimeException(
                "Handler for '{$eventName}' already registered",
            );
        }

        $this->services[$serviceName][$eventName] = new EventHandlerInfo($handler, $eventDesc);
    }

    public function subscribe(ReceiverInterface $receiver): void
    {
        $this->isServing = true;

        // Setup topology if receiver supports it
        if ($receiver instanceof SetuperInterface) {
            $receiver->setup($this->name, $this->getServiceInfos());
        }

        // Start receiving messages
        $receiver->receive(function (Metadata $metadata, string $data): void {
            $this->processEvent($metadata, $data);
        });
    }

    private function processEvent(Metadata $metadata, string $data): void
    {
        $serviceName = $metadata->getServiceName();
        $eventName = $metadata->getEventName();

        if (!isset($this->services[$serviceName][$eventName])) {
            throw new UnprocessableEventException(
                "No handler registered for event: {$metadata->getType()}",
            );
        }

        $handlerInfo = $this->services[$serviceName][$eventName];

        // Decode the message - encoding errors are unprocessable
        try {
            $codec = $this->codecRegistry->getCodec($metadata->getDataContentType());
            $message = $codec->decode($data, $handlerInfo->getEventDesc()->getMessageClass());
        } catch (EncodingException $e) {
            throw new UnprocessableEventException(
                "Failed to decode event {$metadata->getType()}: " . $e->getMessage(),
                previous: $e,
            );
        }

        // Validate handler method exists
        $method = $handlerInfo->getEventDesc()->getHandlerMethod();
        $handler = $handlerInfo->getHandler();

        if (!method_exists($handler, $method)) {
            throw new UnprocessableEventException(
                sprintf(
                    "Handler method '%s' does not exist on %s",
                    $method,
                    get_class($handler),
                ),
            );
        }

        // Create context
        $context = new EventContext($metadata);

        // Build interceptor chain
        $invokeHandler = function (EventContext $ctx, $msg) use ($handler, $method): void {
            $handler->$method($ctx, $msg);
        };

        $chain = $invokeHandler;
        foreach (array_reverse($this->interceptors) as $interceptor) {
            $next = $chain;
            $chain = static function (EventContext $ctx, $msg) use ($interceptor, $next): void {
                $interceptor->intercept($ctx, $msg, $next);
            };
        }

        $chain($context, $message);
    }

    /**
     * @return ServiceInfo[]
     */
    public function getServiceInfos(): array
    {
        $infos = [];
        foreach ($this->services as $serviceName => $events) {
            $infos[] = new ServiceInfo($serviceName, array_keys($events));
        }
        return $infos;
    }

    public function withInterceptor(SubscriberInterceptor $interceptor): self
    {
        $this->interceptors[] = $interceptor;
        return $this;
    }
}
