# Reference implementation of the [IHE SOLE](https://wiki.ihe.net/index.php/Standardized_Operational_Log_of_Events_(SOLE)) Standard
Built using [PHP](https://php.net/) v7 and the [SLIM](http://www.slimframework.com/) v3 framework. 100% Work in progress!


# Usage 
Soon, a copy of this will be hosted by the [SIIM Hackathon](https://siim.org/page/siim_hackathon) for anyone to use with a **free** API key. Otherwise, you can choose to host your own copy by following the installation instructions below.

## Submit bulk events
POST the JSON representation of your events to `/bulk-syslog-events`. Look in the `samples` directory for some sample events to play with. Example below using curl in BASH:
```
curl --request POST \
  --url http://localhost:8000/bulk-syslog-events \
  --header 'accept: application/json' \
  --header 'cache-control: no-cache' \
  --header 'content-type: application/json' \
  --data 'YOUR_JSON_EVENTS_HERE'
```
You will not get any content back, only HTTP response codes as explained here:
* 204 all went well!
* 400 Known error (status message to explain, e.g. malformatted JSON)
* 500 Unkown error, see `logs/app.log` for more information.



# Installation instructions
## Pre-requisites
* Web server, e.g. [Apache HTTP Server](https://httpd.apache.org/) or [NGINX](https://www.nginx.com/)
* Any plug-ins/dependencies necessary for your web server to execute PHP code
* [Composer](https://getcomposer.org/) for PHP dependency management
* [PostgreSQL](https://www.postgresql.org/) Database - others (e.g. MySQL) may work, but I have not attempted to use them.
* [SQL Power Architect](http://www.bestofbi.com/page/architect) to visualize the database schema and export database creation SQL script. The free edition is sufficient for our needs here.


## Installation steps
* In SQL Power Architect, open `db-schema/sole-repo.architect` then click **Tools->Forward Engineer** select **PostgreSQL** as the type then click OK. If you are prompted about any warnings/errors click to Ignore Warnings. Copy the SQL script shown.
* In your favourite PostgreSQL management tool (`psql`, PhpPgAdmin, PgAdmin...etc), create a new database and user (defaults are ihesole for DB name, username and password). Then past and execute the SQL script from the step above.
* In the project directory, run `composer install` to download all dependencies
* Deploy to your web server, which could mean copying/moving the project to the web server, using a symlink or other ways.
* Using tools like curl or Postman, you should be able to use the RESTful API.



# Want to contribute?
Please fork, make changes as appropriate and submit a pull request.

# License
MIT license - see [LICENSE](LICENSE) for verbiage.
