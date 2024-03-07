DEMO_DIR ?= demo
BIN_DIR ?= bin
VAR_DIR ?= var

EXCHANGE_NAME = example.books.v1
RABBITMQ_ADDR = localhost:15672
RABBITMQ_USER = guest
RABBITMQ_PASS = guest

WORKDIR = /var/www/application
PHP_IMAGE = protoevent-php_php

.PHONY: compile-phar
compile-phar:
	cd $(BIN_DIR) && php --define phar.readonly=0 ./compile && chmod +x ../dist/protoc-gen-php-eventbus.phar

PHONY: install-vendors
install-vendors:
	composer install

PHONY: run-test-generator
run-test-generator:
	cat < $(VAR_DIR)/code_generator_request.pb.bin | ./bin/protoc-gen-php-eventbus

PHONY: generate-protoevents
generate-protoevents:
	cd $(DEMO_DIR) && buf generate --include-imports

PHONY: start-receiver
start-receiver:
	docker exec -it $$(docker ps -qf "ancestor=$(PHP_IMAGE)") sh -c 'cd $(WORKDIR)/$(DEMO_DIR) && php demo_receive.php'

PHONY: publish-event
publish-event:
	docker exec -it $$(docker ps -qf "ancestor=$(PHP_IMAGE)") sh -c 'cd $(WORKDIR)/$(DEMO_DIR) && php demo_publish.php'

PHONY: start-pubsub
start-pubsub:
	docker exec -it $$(docker ps -qf "ancestor=$(PHP_IMAGE)") sh -c 'cd $(WORKDIR)/$(DEMO_DIR) && php demo_pubsub.php'

PHONY: setup-exchange
setup-exchange:
	curl -i -u $(RABBITMQ_USER):$(RABBITMQ_PASS) -H "Content-Type: application/json" -XPUT http://$(RABBITMQ_ADDR)/api/exchanges/%2F/$(EXCHANGE_NAME) -d'{"type":"direct","durable":true}'
