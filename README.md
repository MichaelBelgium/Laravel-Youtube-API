# Laravel Youtube API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/michaelbelgium/laravel-youtube-api.svg?style=flat-square)](https://packagist.org/packages/michaelbelgium/laravel-youtube-api)
[![Total Downloads](https://img.shields.io/packagist/dt/michaelbelgium/laravel-youtube-api.svg?style=flat-square)](https://packagist.org/packages/michaelbelgium/laravel-youtube-api)

This package provides a simple youtube api for your Laravel application. It is based on my non-laravel package [Youtube API](https://github.com/MichaelBelgium/Youtube-to-mp3-API).

## Installation

* Install the package via composer:

```bash
composer require michaelbelgium/laravel-youtube-api
```

* Optional: publish the config file and edit if u like:
```bash
php artisan vendor:publish --tag=youtube-api-config
```

* This package uses [the public disk](https://laravel.com/docs/7.x/filesystem#the-public-disk) of Laravel. Run this command to create a symbolic link to the public folder so that converted Youtube downloads are accessible:
```bash
php artisan storage:link
```

* Acquire a Google API key at the [Google Developer Console](https://console.developers.google.com) for the API "Youtube Data API v3". Use this key in the environment variable `GOOGLE_API_KEY` - that this packages uses

# Software

On the server where your laravel app is located, you'll need to install some packages.

* Install ffmpeg (+ libmp3lame - see [this wiki](https://github.com/MichaelBelgium/Youtube-to-mp3-API/wiki/Installing-"ffmpeg"-and-"libmp3lame"-manually) for tutorial)
* [install youtube-dl](http://ytdl-org.github.io/youtube-dl/download.html)

# API Usage

This package adds 3 api routes. The route prefix, `/ytconverter/` in this case, is configurable.

* POST /ytconverter/convert
* DELETE /ytconverter/{id}
* GET /ytconverter/search/{q} 

Check the wiki page of this repository for more information about the routes.

## API Auth

If needed, you can protect the API routes with the api:auth middleware by setting `enable_auth` to `true` in the configuration. A token is then required to make requests to the routes by passing it as bearer token or as query parameter `api_token`. See [Laravel 6.x docs](https://laravel.com/docs/6.x/api-authentication#passing-tokens-in-requests)