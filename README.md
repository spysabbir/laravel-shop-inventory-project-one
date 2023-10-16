<p align="center"><a href="https://shop-inventory.spysabbir.com/" target="_blank"><img src="#"></a></p>

## About This Project

This is project is the Inventory Management System. Built with `Laravel` & various packages included.

## Setup

- First of all we have to `clone` the project at our local machine using below command
 ```
git clone https://github.com/spysabbir/laravel-shop_inventory-application.git
``` 
- Now change the command line present working directory (pwd) by
 ```
cd laravel-shop_inventory-application
``` 
- Now with help of `composer` download all required packages those need to run this laravel project
 ```
composer install
``` 
- Now, we need to copy the .env.example file as .env file for our laravel project. Use below command to copy the file
 ```
cp .env.example .env
``` 
- Currently our project do not have any key, we have generate it using
 ```
php artisan key:generate
``` 
- Basic setup is done at this point, now we have work on `.env`. Below changes should be done at .env file

Variable Name | Description
--- | ---
DB_* | database settings to connect the database with this project
MAIL_* | database settings to send email via SMTP

- Now migrate and seed the database using
 ```
php artisan migrate --seed
``` 

- At last, we can now run the project using
 ```
php artisan serve
``` 

- Demo login credentials 
 ```
For Super Admin:- 
Email: superadmin@email.com
For Admin:- 
Email: admin@email.com
For Manager:- 
Email: manager@email.com
Password: 12345678
``` 
