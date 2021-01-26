
install:
	docker build -t tipsy-symfony ./docker/symfony
	docker run --rm -v ${PWD}:/app -w /app tipsy-symfony composer update

run:
	docker run --rm --network host -v ${PWD}:/app -w /app tipsy-symfony symfony server:start

test:
	docker run --rm -v ${PWD}:/app -w /app tipsy-symfony ./bin/phpunit

symfony-cli:
	docker run --rm -it --network host -v ${PWD}:/app -w /app tipsy-symfony bash
