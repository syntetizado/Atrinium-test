# Atrinium-test v2

notes:
- I used CLI server for this build
- The database has changed, please import this new one

Steps to install app:

	- Install Symfony:
		$ composer create-project symfony/website-skeleton atrinium

	- Configure .env:
		DATABASE_URL="mysql://testuser:@127.0.0.1:3306/atrinium_db?serverVersion=5.7"
		#Create 'testuser' on phpMyAdmin if don't exist (no password)
		#Please, check if server version is ok

	- Import database on a MySQL server

	- move console symbol to project root

	- Just in case, lets ask for pagerfanta-bundle:
		$ composer require pagerfanta/pagerfanta

	- Now, copy all files on root (/src /config /templates /public) 

	- use Symfony CLI (on root):
		$ symfony server:start
