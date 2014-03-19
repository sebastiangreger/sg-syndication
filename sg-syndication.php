<?php

/*
Plugin Name: sg-syndication
Plugin URI: https://github.com/sebastiangreger/sg-syndication
Description: Yet another attempt at a POSSE plug-in for WordPress
Version: 0.3
Author: Sebastian Greger
Author URI: http://sebastiangreger.net
License: GPL3
*/

/*  Copyright 2014 Sebastian Greger

    The development of this software was made possible using the following components:

    - codebird-php by Jublo IT Solutions
      https://github.com/jublonet/codebird-php
      Licensed Under: GNU General Public License v3 (GPL-3)

    - phpFlickr by Dan Coulter
      http://code.google.com/p/phpflickr/
      Licensed Under: GNU Lesser GPL

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, see http://www.gnu.org/licenses/

*/


/**
* Block access if called directly
*/
if ( !function_exists( 'add_action' ) ) {
	echo "This is a plugin file, direct access denied!";
	exit;
}


/**
* Load admin UI functionalities if admin ui
*/
if ( is_admin() ) {
    require_once dirname( __FILE__ ) . '/sg-syndication-admin.php';
}

