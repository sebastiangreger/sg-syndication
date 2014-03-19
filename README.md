sg-syndication
==============

Yet another attempt at a POSSE syndication plug-in for WordPress

## Description

This plugin is the attempt to implement [#POSSE](http://indiewebcamp.org/POSSE), with the user in full control over otherwise automatic submission of syndicated posts to silo services. It follows considerations of a [identity-content-audience concept](http://sebastiangreger.net/2014/01/identity-content-audience-and-the-independent-web/) and was primarily created for myself; open sourced as a contribution to the [#indieweb](http://indiewebcamp.org)

The design rationale is presented in a [detailed blog post](http://sebastiangreger.net/2014/03/audience-context-conscious-posse-plugin-wordpress).

The code makes use of open source php libraries for handling the API communication:

* [codebird-php](https://github.com/jublonet/codebird-php) by Jublo IT Solutions, licensed Under: GNU General Public License v3 (GPL-3)
* [phpFlickr](http://code.google.com/p/phpflickr/) by Dan Coulter, licensed Under: GNU Lesser GPL

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License, version 3, as published by the Free Software Foundation.

## Features

* Selection of syndication actions in post ui
* Publication of Twitter message when updating/publishing a post
* Storage of a syndicated link's url in database

### Known issues

* The character count for Twitter does not take into account url shortening and is therefore slightly off
* The entire plugin is a bit wobbly and very hacky, but that's the nature of it

### Roadmap, ideas for future development

* Add Google Plus, LinkedIn, Instagram, Facebook
* Enable the use of two accounts for the same silo (e.g. personal and project Twitter)
* Presets for automatic selection of syndication by category or post type
* Limiting certain services to particular categories or post types
* Support for "Publish later" (save draft of POSSE msg and syndicate on scheduled publishing)
* Improve the setup process to guide the user through getting the API credentials
* Modularize the code to use the api channels also for other purposes (e.g. replies on syndicated comments)

## Changelog

Project maintained on github at [sebastiangreger/sg-syndication](https://github.com/sebastiangreger/sg-syndication).

### 0.3

* added support for FLICKR
* several ui improvements
* made ui code more modular to enable easy addition of more services

### 0.2

* slightly more modular api handler code to enable easy addition of more services

### 0.1

* added support for TWITTER
* initial version