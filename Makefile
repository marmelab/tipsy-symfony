
install:
	docker-compose build
	docker-compose run --rm symfony bash -ci 'composer install'
	docker-compose run --rm symfony bash -ci 'composer update'

run:
	docker-compose up -d

test:
	docker-compose run --rm symfony bash -ci './bin/phpunit'

symfony-cli:
	docker run --rm -it --network host -v ${PWD}:/app -w /app tipsy-symfony bash

deploy:
	git archive -o tipsy.zip HEAD
	scp -i ${key} tipsy.zip ubuntu@ec2-3-250-16-46.eu-west-1.compute.amazonaws.com:~/
	ssh -i ${key} ubuntu@ec2-3-250-16-46.eu-west-1.compute.amazonaws.com \
	'unzip -ou tipsy.zip -d tipsy && \
	cd tipsy &&\
	cp .env.prod .env &&\
	make install &&\
	make run'
	rm tipsy.zip
