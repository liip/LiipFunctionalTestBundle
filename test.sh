#!/usr/bin/env bash

docker build ./ --tag lftb

docker run -i -t --rm --volume "$PWD/.:/app" --workdir /app lftb sh -c 'composer update'

#docker run -i -t --rm --volume "$PWD/.:/app" --workdir /app lftb sh -c 'php vendor/phpunit/phpunit/phpunit --exclude-group "" --testdox --filter testIndexAuthenticationLoginClient --debug'
docker run -i -t --rm --volume "$PWD/.:/app" --workdir /app lftb sh -c 'php vendor/phpunit/phpunit/phpunit --exclude-group "" --testdox'
#docker run -i -t --rm --volume "$PWD/.:/app" --workdir /app lftb sh -c 'php vendor/phpunit/phpunit/phpunit --exclude-group "" --testdox --debug -vvv'
#docker run -i -t --rm --volume "$PWD/.:/app" --workdir /app lftb sh -c 'php vendor/phpunit/phpunit/phpunit --exclude-group ""'
#docker run -i -t --rm --volume "$PWD/.:/app" --workdir /app lftb sh -c 'php vendor/phpunit/phpunit/phpunit --exclude-group "" tests/Test/WebTestCaseConfigLeanFrameworkTest.php'
#docker run -i -t --rm --volume "$PWD/.:/app" --workdir /app --publish 8000:8000 lftb sh -c 'cd tests/App/ ; php -S 0.0.0.0:8000'
#docker run -i -t --rm --volume "$PWD/.:/app" --workdir /app lftb sh -c 'php vendor/phpunit/phpunit/phpunit --migrate-configuration'
