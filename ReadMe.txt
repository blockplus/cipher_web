Configure The "php.ini" File
    file_uploads = On

-------------
write permission
    sudo chown your_user:www-data images/
    sudo find images/ -type d -exec chmod 770 {} +
    sudo find images/ -type f -exec chmod 660 {} +
-------------