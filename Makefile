
install:
	docker build -t tipsy-symfony ./docker/symfony

run:
	docker run --network host -v ${PWD}:/app -w /app tipsy-symfony symfony server:start
