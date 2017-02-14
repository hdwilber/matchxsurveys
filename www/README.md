# MEF-server based on Slim 3 API 
This is the server for MEF, a tool to give results according to a questionnaire.

This Project uses [Spot](http://phpdatamapper.com/) as persistence layer,  [Monolog](https://github.com/Seldaek/monolog) for logging, and [Fractal](http://fractal.thephpleague.com/) as serializer. [Vagrant](https://www.vagrantup.com/) virtualmachine config and [Paw](https://geo.itunes.apple.com/us/app/paw-http-rest-client/id584653203?mt=12&at=1010lc2t) project files are included for easy development.

## Install

Install all dependencies using: 

``` bash
$ composer install
```

## Usage

### On development stage
``` bash
$ php -S [server:port] -t public public/index.php
```

### On production stage (TODO)
``` bash
$ cd app
$ vagrant up
```


## License
Not defined, yet.

