#!/bin/bash

./vendor/bin/phpunit --coverage-html ./coverage-report

printf '%20s\n' | tr ' ' -

./vendor/bin/apigen.php --source ./src --destination ./api