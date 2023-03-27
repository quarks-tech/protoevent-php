.PHONY: compile-phar
compile-phar:
	cd bin && php --define phar.readonly=0 ./compile && chmod +x ../dist/protoc-gen-php-eventbus.phar

PHONY: install-vendors
install-vendors:
	composer install

PHONY: run-test-generator
run-test-generator:
	cat < ./var/code_generator_request.pb.bin | ./bin/protoc-gen-php-eventbus

PHONY: proto-cloudevents
proto-cloudevents:
	protoc --php_out=src/EventBus resources/cloundevents.proto
