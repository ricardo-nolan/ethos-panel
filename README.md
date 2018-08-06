# ethos-panel

An alternate control panel for Ethos mining rigs.

Also available at https://ethos-panel.com

Includes Rigchecker to restart rig if a GPU crashes, and an autofan script which adjusts fans according to a range of temperatures.

## Screenshots

https://imgur.com/a/F3tfM

## Setup

To setup on your own server, simply copy/clone the files to your webserver, import ethos-panel.sql into your MySQL server, and update config/config.json with your database details.

You can also use the installation script as follows:
Log into your server via ssh, and run:
(assuming /var/www/html is empty)

    chown -R www-data:www-data /var/www/html
    cd /var/www/html
    git clone https://github.com/foraern/ethos-panel.git


#### If installing on digital ocean

Below are the correct steps for installing.
(assuming /var/www/html is empty)

    cd /var/www/html
    git clone https://github.com/foraern/ethos-panel.git .
    sudo chown -R www-data:www-data /var/www/html
    sudo a2enmod rewrite 
    sudo apt-get update
    sudo apt-get install curl
    sudo apt-get install php-curl
    sudo service apache2 restart
    crontab -e 




in crontab add:

    */10 * * * * cd /var/www/html/ && ./cron

then point your browser to: `http://yourserver/install/install.php` and fill in with appropriate info.

For profitability calculators, please obtain an API key at https://www.coinwarz.com/v1/api/documentation and provide it in config.json

## Please be aware, this is a project done on my free time (which I don't have a lot of). Due to the summer, and lack of adequate cooling facilities, I'm not mining myself at the moment, so I only carry out reported fixes, when time allows.
## Please do not expect active development until winter.

## Promises of donations for work do not constitute any obligations from my side. I've been promised donations for work multiple times, and as you can see from the donation addresses, I've received a total of one donation in all the time this has been in development. So please do not expect me to stop what I'm doing to accomodate your request. I'll get to it when I have time.


## Donations

You can send donations to any of the following addresses:

* BTC: `38c6SgdEUR4JwgZY3nKgDWjjz6adLZnJ1x`
* BCH: `1CGqJthGPaF3ws5miczof7TxVCnxf77g2Y`
* ETH: `0x534cf0F7a388f20a8EeC1B074CE233F882126E3D`
