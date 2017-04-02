# MatchXSurveys - Maximum Matching Surveys, API backend
This is the server backend API to handle MatchXSurveys

### Still ON Development

This Project uses [Spot](http://phpdatamapper.com/) as persistence layer,  [Monolog](https://github.com/Seldaek/monolog) for logging, and [Fractal](http://fractal.thephpleague.com/) as serializer. [Vagrant](https://www.vagrantup.com/) virtualmachine config and [Paw](https://geo.itunes.apple.com/us/app/paw-http-rest-client/id584653203?mt=12&at=1010lc2t) project files are included for easy development.

### Still on development. HELP are Welcome!

MatchXSurveys backend API has been coded to support the easy creation and behavior applying to questions, in order to create a flexible, dynamic platform for surveys.
The main characteristic are: 
- Questionaries creation/edition. An easily way to create questionaries, according to question types and apply a dynamic behaviour in order to show, hide and jump in the questionary according to previously answered questions.
- Register. Since there are being developed many types of questions, such as selections, levels, text and so on, the server is able to save all data and an easy retrieving.
- Statistics. According to data entered, the server can be useful to generate statistics.

In order to test the client version, you can check [Mef](https://github.com/hdwilber/mef-client) as an example in Angular2 using this API.

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

## Remarks
    This code is configured to work in an OpenShift server or local.

## License
Not defined, yet.

