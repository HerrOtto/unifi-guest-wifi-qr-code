# UniFI Guest WiFi Password rotation and QR Code Generator

The UniFI Dynamic Guest WiFi Password Changer is a PHP application designed to bolster security by regularly rotating the pre-shared key (PSK) for the guest WiFi network. Additionally, it streamlines the process for guests to connect to the WiFi network by generating a QR code containing the network's SSID and the current PSK.

# Features
* **Regular PSK Rotation**: The application allows administrators to schedule automatic rotation of the guest WiFi's pre-shared key at defined intervals. This helps in preventing unauthorized access and improving overall network security.
* **QR Code Generation**: Guests can easily connect to the WiFi network by scanning the generated QR code with their devices. The QR code contains the SSID and the current pre-shared key, eliminating the need for manual input.
* **Web Interface**: The application features a straightforward web interface tailored for conveniently displaying login information on a tablet within your visitor room.

# Requirements
* Web server with PHP support (e.g., Apache)
* PHP 7.0 or later
* "UniFI Cloud Key" or another device running the "UniFI Network" application

# Installation (Debian 12)
* Add a guest WiFi to your cloud key.
* Install Debian 12 with Apache Webserver.
* Enable mod_rewrite:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```
* Allow htaccess in /var/www/html: 
```bash
sudo nano /etc/apache2/sites-available/000-default.conf
```
Add the following inside the <VirtualHost *:80> block:
````
<Directory /var/www/html>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
````
* Clone this GIT repository to /var/www/html.
* Remove index.html from /var/www/html.
* Modify configuration and UniFI credentials in "_init.php".
* Set up a scheduled task (e.g., cron job) to run the change_psk.php script at regular intervals for automatic PSK rotation:
```bash
sudo crontab -e
```
Add the following line to your crontab to change the PSK every night:
````
0 0 * * * /usr/bin/php /var/www/html/newPSK.php
````

# Usage
* Access the web interface using a web browser.
* Configure the PSK rotation interval according to your security needs by changing crontab.
* Add "http://debian-webserver-ip/" to your tablet's start page using a kiosk browser.

# Disclaimer
This application is provided as-is without any warranties. Use at your own risk. The developers are not responsible for any security breaches or other issues that may arise from the use of this application.

# License
This application is licensed under the [MIT License](https://opensource.org/license/mit/)
