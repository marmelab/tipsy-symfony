
install:
	docker build -t tipsy-symfony ./docker/symfony

run:
	docker run --rm --network host -v ${PWD}:/app -w /app tipsy-symfony symfony server:start

test:
	echo "TO BE IMPLEMENT":
	false;

symfony-cli:
	docker run --rm -it --network host -v ${PWD}:/app -w /app tipsy-symfony bash
