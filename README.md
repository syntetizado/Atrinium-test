# Atrinium-test

notes:
- User role can be changed clicking on role (test purpose, dont mind as security issue)
- This project was made in 3 days
- I used CLI server for this build

Steps to install app:
	- Install Symfony:
		$ composer create-project symfony/website-skeleton atrinium

	- Configure .env:
		DATABASE_URL="mysql://testuser:@127.0.0.1:3306/atrinium_db?serverVersion=5.7"
		#Create 'testuser' on phpMyAdmin if don't exist (no password)
		#Please, check if server version is ok

	- move console symbol to project root

	- Just in case, lets ask for pagerfanta-bundle:
		$ composer require white-october/pagerfanta-bundle

	- Now, copy all files on root (/src /config /templates)

	- use Symfony CLI (on root):
		$ symfony server:start
