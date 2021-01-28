
install:
	docker-compose build
	docker-compose run --rm symfony bash -ci 'composer install'
	docker-compose run --rm symfony bash -ci 'composer update'

run:
	docker-compose up --build

test:
	docker-compose run --rm symfony bash -ci './bin/phpunit'

symfony-cli:
	docker run --rm -it --network host -v ${PWD}:/app -w /app tipsy-symfony bash
