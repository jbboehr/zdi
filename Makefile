
all: vendor

coverage: vendor clean-test tests/tmp
	./vendor/bin/phpunit --coverage-text --coverage-html=reports

clean-test:
	rm -Rf tests/tmp
clean: clean-test

tests/tmp:
	mkdir tests/tmp

test: vendor clean-test tests/tmp
	./vendor/bin/phpunit

vendor: composer.json composer.lock

.PHONY: all clean clean-test test
