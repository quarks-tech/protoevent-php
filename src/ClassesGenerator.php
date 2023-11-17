<?php

namespace Quarks;

use google\protobuf\compiler\CodeGeneratorRequest;
use google\protobuf\compiler\CodeGeneratorResponse;
use google\protobuf\compiler\CodeGeneratorResponse\File;
use google\protobuf\DescriptorProto;
use google\protobuf\FileDescriptorProto;
use Google\Protobuf\Internal\FieldDescriptorProto;
use Laminas\Code\Generator\AbstractMemberGenerator;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\InterfaceGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\TypeGenerator;
use Protobuf\Collection;

class ClassesGenerator
{
    private const OPTION_TAG_PHP_NAMESPACE = 41;
    private const OPTION_TAG_ENABLED = 1081;

    public function generate(CodeGeneratorRequest $request): CodeGeneratorResponse
    {
        $response = new CodeGeneratorResponse();

        if (! $request->getProtoFileList()) {
            throw new \LogicException("Empty list of proto-files received.");
        }

        foreach ($request->getProtoFileList() as $protoFile) {
            /** @var $protoFile FileDescriptorProto */

            if (sizeof($events = $this->findEvents($protoFile)) == 0) {
                continue;
            }

            if (empty($phpNamespace = $this->findPhpNamespaceOption($protoFile))) {
                continue;
            }

            $serviceDescriptor = $this->createServiceDescriptor($protoFile);
            $publisher = $this->createPublisher($protoFile);
            $receiver = $this->createReceiver($protoFile);

            foreach ($events as $event) {
                /** @var $event DescriptorProto */

                $publisherInterface = $this->createPublisherInterface($event, $phpNamespace);

                $publisher->setImplementedInterfaces(array_merge(
                    $publisher->getImplementedInterfaces(),
                    [$phpNamespace . '\\EventBus\\Publisher\\' . $event->getName() . 'PublisherInterface'],
                ));

                $response->addFile($this->createFile([$publisherInterface], $phpNamespace, '/EventBus/Publisher/' . $event->getName() . 'PublisherInterface.php'));

                $receiverInterface = $this->createReceiverInterface($event, $phpNamespace);
                $response->addFile($this->createFile([$receiverInterface], $phpNamespace, '/EventBus/Receiver/' . $event->getName() . 'HandlerInterface.php'));

                $this->addEventToPublisher($publisher, $event, $protoFile);
                $this->addHandlerToReceiver($receiver, $event, $protoFile);
            }

            $response->addFile($this->createFile([$receiver], $phpNamespace, '/EventBus/Receiver/Receiver.php'));
            $response->addFile($this->createFile([$serviceDescriptor], $phpNamespace, '/EventBus/ServiceDescriptor.php'));
            $response->addFile($this->createFile([$publisher], $phpNamespace, '/EventBus/Publisher/Publisher.php'));
        }

        return $response;
    }

    private function createPublisherInterface(DescriptorProto $message, string $phpNamespace): InterfaceGenerator
    {
        $publisherInterface = new InterfaceGenerator();
        $publisherInterface
            ->setName($message->getName() . 'PublisherInterface')
            ->setNamespaceName($phpNamespace . '\\EventBus\\Publisher')
            ->addMethodFromGenerator(
                MethodGenerator::fromArray([
                    'name' => 'publish' . $message->getName(),
                    'parameters' => [
                        [
                            'name' => 'event',
                            'type' => $phpNamespace . '\\' . $message->getName(),
                        ],
                    ],
                ])
            );
        return $publisherInterface;
    }

    private function createReceiverInterface(DescriptorProto $message, string $phpNamespace): InterfaceGenerator
    {
        $receiverInterface = new InterfaceGenerator();
        $receiverInterface
            ->setName($message->getName() . 'HandlerInterface')
            ->setNamespaceName($phpNamespace . '\\EventBus\\Receiver')
            ->addMethodFromGenerator(
                MethodGenerator::fromArray([
                    'name' => 'handle' . $message->getName(),
                    'parameters' => [
                        [
                            'name' => 'event',
                            'type' => $phpNamespace . '\\' . $message->getName(),
                        ],
                    ],
                ])
            );

        return $receiverInterface;
    }

    private function addEventToPublisher(ClassGenerator $publisher, DescriptorProto $message, FileDescriptorProto $file): void
    {
        $phpNamespace = $this->findPhpNamespaceOption($file);

        $publisher
            ->addMethodFromGenerator(
                MethodGenerator::fromArray([
                    'name' => "publish{$message->getName()}",
                    'body' => "\$this->publisher->publish(\$event, '{$file->getPackage()}.{$this->removeEventPostfix($message->getName())}', \$options);",
                    'parameters' => [
                        [
                            'name' => 'event',
                            'type' => $phpNamespace . '\\' . $message->getName(),
                        ],
                        [
                            'name' => 'options',
                            'type' => 'array',
                            'defaultValue' => []
                        ]
                    ],
                ])
            );
    }

    private function addHandlerToReceiver(ClassGenerator $receiver, DescriptorProto $message, FileDescriptorProto $file): void
    {
        $phpNamespace = $this->findPhpNamespaceOption($file);

        $receiver->addMethodFromGenerator(
            MethodGenerator::fromArray([
                'name' => "register{$message->getName()}Handler",
                'body' => "
\$event = \$this->serviceDescriptor->findEventByName('{$message->getName()}');

\$this->receiver->register(\$event);
\$this->dispatcher->registerHandler(\$event->getFullName(), [\$handler, 'handle{$message->getName()}']);",
                'parameters' => [
                    [
                        'name' => 'handler',
                        'type' => $phpNamespace . '\\EventBus\\Receiver\\' . $message->getName() . 'HandlerInterface',
                    ],
                ],
            ])
        );
    }

    private function generateServiceDescriptorEventsMapItems(Collection $messages, string $package, string $namespace): string
    {
        $format = "\t'%s' => new EventDescriptor('%s.%s', \\%s::class),\n";
        $result = '';

        foreach ($messages as $message) {
            if (!$this->isAppropriate($message)) {
                continue;
            }

            $result .= sprintf(
                $format,
                $message->getName(),
                $package,
                $this->removeEventPostfix($message->getName()),
                $namespace .'\\' . $message->getName()
            );
        }

        return $result;
    }

    private function findPhpNamespaceOption(FileDescriptorProto $file): string
    {
        return $file->getOptions()?->unknownFieldSet()[self::OPTION_TAG_PHP_NAMESPACE]?->value ?? '';
    }

    private function removeEventPostfix(string $name): string
    {
        return str_replace("Event", "", $name);
    }

    private function transformNamespaceToPath(string $class): string
    {
        return str_replace('\\', DIRECTORY_SEPARATOR, $class);
    }

    private function createPublisher(FileDescriptorProto $file): ClassGenerator
    {
        $phpNamespace = $this->findPhpNamespaceOption($file);

        $publisher = new ClassGenerator();
        $publisher
            ->setName('Publisher')
            ->setNamespaceName($phpNamespace . '\\EventBus\\Publisher')
            ->addPropertyFromGenerator(
                PropertyGenerator::fromArray([
                    'name' => 'publisher',
                    'omitdefaultvalue' => true,
                    'type' => TypeGenerator::fromTypeString('Quarks\EventBus\Publisher'),
                    'visibility' => AbstractMemberGenerator::FLAG_PRIVATE,
                ])
            )
            ->addMethodFromGenerator(
                MethodGenerator::fromArray([
                    'name'       => '__construct',
                    'body'       => '$this->publisher = $publisher;',
                    'parameters' => [
                        [
                            'name' => 'publisher',
                            'type' => 'Quarks\EventBus\Publisher',
                        ],
                    ],
                ])
            );

        return $publisher;
    }

    private function createServiceDescriptor(FileDescriptorProto $file): ClassGenerator {
        $phpNamespace = $this->findPhpNamespaceOption($file);

        $serviceDescriptor = new ClassGenerator();
        $serviceDescriptor
            ->setName('ServiceDescriptor')
            ->setNamespaceName($phpNamespace . '\\EventBus')
            ->addUse('Quarks\EventBus\Descriptor\EventDescriptor')
            ->addUse('Quarks\EventBus\Exception\UnknownEventException')
            ->addPropertyFromGenerator(
                PropertyGenerator::fromArray([
                    'name' => 'events',
                    'defaultvalue' => [],
                    'type' => TypeGenerator::fromTypeString('array'),
                    'visibility' => AbstractMemberGenerator::FLAG_PRIVATE,
                ])
            )
            ->addMethodFromGenerator(
                MethodGenerator::fromArray([
                    'name'       => '__construct',
                    'body'       => "\$this->events = [\n" . $this->generateServiceDescriptorEventsMapItems($file->getMessageTypeList(), $file->getPackage(), $phpNamespace) . "];",
                ])
            )
            ->addMethodFromGenerator(
                MethodGenerator::fromArray([
                    'name'       => 'create',
                    'body'       => 'return new self();',
                    'returnsreference' => false,
                    'static'     => true,
                ])
            )
            ->addMethodFromGenerator(
                MethodGenerator::fromArray([
                    'name'       => 'getName',
                    'body'       => "return '{$file->getPackage()}';",
                    'returntype' => 'string',
                ])
            )
            ->addMethodFromGenerator(
                MethodGenerator::fromArray([
                    'name'       => 'findEventByName',
                    'parameters' => [
                        [
                            'name' => 'eventName',
                            'type' => 'string',
                        ],
                    ],
                    'body'       => "
if (!isset(\$this->events[\$eventName])) {
    throw new UnknownEventException(\$eventName);
}

return \$this->events[\$eventName];",
                    'returntype' => 'Quarks\\EventBus\\Descriptor\\EventDescriptor',
                ])
            );

        return $serviceDescriptor;
    }

    private function createReceiver(FileDescriptorProto $file): ClassGenerator
    {
        $phpNamespace = $this->findPhpNamespaceOption($file);

        $receiver = new ClassGenerator();
        $receiver
            ->setName('Receiver')
            ->setNamespaceName($phpNamespace . '\\EventBus\\Receiver')
            ->addPropertyFromGenerator(
                PropertyGenerator::fromArray([
                    'name' => 'receiver',
                    'omitdefaultvalue' => true,
                    'type' => TypeGenerator::fromTypeString('Quarks\EventBus\BaseReceiver'),
                    'visibility' => AbstractMemberGenerator::FLAG_PRIVATE,
                ])
            )
            ->addPropertyFromGenerator(
                PropertyGenerator::fromArray([
                    'name' => 'serviceDescriptor',
                    'omitdefaultvalue' => true,
                    'type' => TypeGenerator::fromTypeString($phpNamespace . '\\EventBus\\ServiceDescriptor'),
                    'visibility' => AbstractMemberGenerator::FLAG_PRIVATE,
                ])
            )
            ->addPropertyFromGenerator(
                PropertyGenerator::fromArray([
                    'name' => 'dispatcher',
                    'omitdefaultvalue' => true,
                    'type' => TypeGenerator::fromTypeString('Quarks\EventBus\Dispatcher\Dispatcher'),
                    'visibility' => AbstractMemberGenerator::FLAG_PRIVATE,
                ])
            )
            ->addMethodFromGenerator(
                MethodGenerator::fromArray([
                    'name'       => '__construct',
                    'body'       => "
\$this->receiver = \$receiver;
\$this->serviceDescriptor = \\{$phpNamespace}\\EventBus\\ServiceDescriptor::create();
\$this->dispatcher = \$dispatcher;",
                    'parameters' => [
                        [
                            'name' => 'receiver',
                            'type' => 'Quarks\EventBus\BaseReceiver',
                        ],
                        [
                            'name' => 'dispatcher',
                            'type' => 'Quarks\EventBus\Dispatcher\Dispatcher',
                        ],
                    ],
                ])
            );

        return $receiver;
    }

    private function createFile(array $classes, string $phpNamespace, string $path): File {
        $file = new File();
        $file->setName($this->transformNamespaceToPath($phpNamespace) . $path);
        $file->setContent(FileGenerator::fromArray(['classes' => $classes])->generate());

        return $file;
    }

    private function findEvents(FileDescriptorProto $protoFile): array
    {
        $events = [];

        foreach ($protoFile->getMessageTypeList() ?? [] as $message) {
            if ($this->isAppropriate($message)) {
                $events[] = $message;
            }
        }

        return $events;
    }

    private function isAppropriate(DescriptorProto $message): bool
    {
        $isEnabled = $message->getOptions()?->unknownFieldSet()[self::OPTION_TAG_ENABLED]?->value ?? 0;

        if (!$isEnabled) {
            return false;
        }

        return str_ends_with($message->getName(), "Event");
    }
}
