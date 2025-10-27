# Docker infraestructure for [iestacio_intranet_backend](https://github.com/chverma/iestacio_intranet_backend)


## Init project
```shell
git submodule init

git submodule update --recursive

## Copy env file
cp {,.}env

cp env iestacio_intranet_backend/.env
```


## Set variables
In `iestacio_intranet_backend/.env` set the variables. Focus on the MYSQL password, it must be the one provided in this .env file.


## Deploy servicies
```shell
docker-compose up -d
```


## Show logs
```shell
docker-compose logs -f web_server
```