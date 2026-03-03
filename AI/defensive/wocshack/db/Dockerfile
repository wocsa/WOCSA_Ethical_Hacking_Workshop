FROM mariadb:latest

COPY ./init.sql /docker-entrypoint-initdb.d/init.sql
COPY ./db_data /var/lib/mysql