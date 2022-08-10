# Deprecated
Please use https://github.com/iBoonie/namesync-redux-flatfile instead.

# NamesyncRedux
- MIT implementation of Namesync's server
- All boards which are forced Anonymous work out of the box and update automatically
- Secure tripcodes work
- Tripcode passwords are **NOT** stored
- Old post data is automatically deleted every 24 hours
- Configurable rate limiting

## Installation
- Import the database located in `/required/database.sql`
- Turn on the **Event scheduler** or old post data will not get deleted
- Add the following Cronjobs
- `*/10 * * * * php /path/to/namesync/required/CRON_GENERATE_BOARD_CACHE.php`
- `* * * * * php /path/to/namesync/required/CRON_UPDATE_BOARD_DATA.php`
- Configuration is located in `/required/config.php`
- **If you are using Apache, you are good to go! If you are not, please deny access to the /required/ folder.**
