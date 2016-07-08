
all: vendor

test: vendor
	./vendor/bin/phpunit

vendor: composer.json composer.lock
