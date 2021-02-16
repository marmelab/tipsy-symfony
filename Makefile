
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

drop-db:
	docker-compose run --rm symfony bash -ci 'symfony console doctrine:schema:drop --force --no-interaction'

create-db:
	docker-compose run --rm symfony bash -ci 'symfony console doctrine:database:create'
	docker-compose run --rm symfony bash -ci 'symfony console doctrine:schema:update --force'

create-migration:
	docker-compose run --rm symfony bash -ci 'php bin/console make:migration'

migrate:
	docker-compose run --rm symfony bash -ci 'symfony console doctrine:migrations:migrate' --no-interaction

ssh:
	ssh -i ${pem} ${user}@${host}
  	
deploy:
	rsync --delete -r -e "ssh -i ${pem}" --filter=':- .gitignore' \
	./ ${user}@${host}:~/tipsy
	ssh -i ${pem} ${user}@${host} \
	'cd tipsy &&\
	make install &&\
	make run'
