# JiraDash

Very crude tool to aggregate worklog data from Jira and Tempo. Very much work in progress and expected to be broken a lot.

## Setup

1. clone repository
1. run ``composer install``
1. create a ``conf/local.neon`` file (reuse structure from ``conf/default.neon``)
1. point web server with PHP 7 to the ``public`` directory  
1. set up a cron job for ``bin/update.php``
