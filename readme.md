# MQTT client

[![Build Status](https://img.shields.io/travis/iPublikuj/mqtt-client.svg?style=flat-square)](https://travis-ci.org/iPublikuj/mqtt-client)
[![Scrutinizer Code Coverage](https://img.shields.io/scrutinizer/coverage/g/iPublikuj/mqtt-client.svg?style=flat-square)](https://scrutinizer-ci.com/g/iPublikuj/mqtt-client/?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/iPublikuj/mqtt-client.svg?style=flat-square)](https://scrutinizer-ci.com/g/iPublikuj/mqtt-client/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/ipub/mqtt-client.svg?style=flat-square)](https://packagist.org/packages/ipub/mqtt-client)
[![Composer Downloads](https://img.shields.io/packagist/dt/ipub/mqtt-client.svg?style=flat-square)](https://packagist.org/packages/ipub/mqtt-client)
[![License](https://img.shields.io/packagist/l/ipub/mqtt-client.svg?style=flat-square)](https://packagist.org/packages/ipub/mqtt-client)

Extension for implementing MQTT client into [Nette framework](https://nette.org) 

## Installation

The best way to install ipub/mqtt-client is using [Composer](http://getcomposer.org/):

```sh
$ composer require ipub/mqtt-client
```

After that you have to register extension in config.neon.

```neon
extensions:
	mqttClient: IPub\MQTTClient\DI\MQTTClientExtension
```

## Documentation

Learn how to connect to MQTT broker and communicate in [documentation](https://github.com/iPublikuj/mqtt-client/blob/master/docs/en/index.md).

***
Homepage [http://www.ipublikuj.eu](http://www.ipublikuj.eu) and repository [http://github.com/iPublikuj/mqtt-client](http://github.com/iPublikuj/mqtt-client).
