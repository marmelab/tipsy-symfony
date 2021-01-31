
install:
	docker-compose build
	docker-compose run --rm symfony bash -ci 'composer install'
	docker-compose run --rm symfony bash -ci 'composer update'
	docker-compose run --rm symfony bash -ci 'symfony console doctrine:schema:update --force'

run:
	docker-compose up --force-recreate -d

test:
	docker-compose run --rm symfony bash -ci './bin/phpunit'

symfony-cli:
	docker run --rm -it --network host -v ${PWD}:/app -w /app tipsy-symfony bash

deploy:
	rsync --delete -r -e "ssh -i ${key}" --filter=':- .gitignore' \
	./ ${user}@${host}:~/tipsy
	ssh -i ${key} ${user}@${host} \
	'cd tipsy &&\
	make install &&\
	make run'
