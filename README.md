# ethos-panel

An alternate control panel for Ethos mining rigs.

Also available at ethos-panel.com

Includes Rigchecker to restart rig if a GPU crashes, and an autofan script which adjusts fans according to a range of temperatures.

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

# WORK IN PROGRESS


## Donations

You can send donations to any of the following addresses:

* BTC: `3JfgwF7t1mnwLkJZHMoRvgxMWj6EiZrVDk`
* BCH: `1Nx8BPnihW3DukNNxywRpkBJ8K83ENzBQn`
* ETH: `0x1281BAF53917c2058fFe7ac9Fa2a1d618E76a45C`
