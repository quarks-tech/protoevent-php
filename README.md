# ProtoEvent Makefile

This Makefile provides convenient commands for working with ProtoEvent application.

## Usage

### Compilation

- **compile-phar**: Compile the PHP Phar file for ProtoEvent.
- **install-vendors**: Install PHP dependencies using Composer.

### Running Tests and Generating Code

- **run-test-generator**: Run the test generator for ProtoEvent.
- **generate-protoevents**: Generate ProtoEvents from .proto files.

### Docker Operations

- **up**: Start the Docker containers defined in the docker-compose file.
- **start-receiver**: Start the ProtoEvent receiver within a Docker container.
- **publish-event**: Publish a ProtoEvent within a Docker container.
- **start-pubsub**: Start the ProtoEvent pubsub within a Docker container.
- **down**: Stop and remove the Docker containers.

### RabbitMQ Setup

- **setup-exchange**: Setup an exchange in RabbitMQ for ProtoEvent.

## Configuration

Before running any commands, make sure to configure the following variables in the Makefile:

- **DEMO_DIR**: Directory containing ProtoEvent demo files.
- **BIN_DIR**: Directory containing compiled binaries.
- **VAR_DIR**: Directory containing variable files.
- **EXCHANGE_NAME**: RabbitMQ exchange name.
- **RABBITMQ_ADDR**: RabbitMQ address.
- **RABBITMQ_USER**: RabbitMQ username.
- **RABBITMQ_PASS**: RabbitMQ password.
- **WORKDIR**: Working directory within the Docker container.
- **PHP_IMAGE**: Docker image name for PHP.

## Installation

1. **Start docker containers**: PHP 8.1 with RabbitMQ-management images
    ```bash
    make up
    ```

2. **Setup demo exchange**: `example.books.v1` exchange will be created
    ```bash
    make setup-exchange
    ```

2. **Publish an event**: The `BookCreatedEvent` will be published on to `example.books.v1` exchange
    ```bash
    make publish-event
    ```

2. **Start the receiver**: The receiver will create the `namespace.service.consumers.v1` queue, bind itself to the `BookCreatedEvent`, and start listening to events to process them.
    ```bash
    make start-receiver
    ```


