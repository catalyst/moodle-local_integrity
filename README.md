![Build Status](https://github.com/catalyst/moodle-local_integrity/actions/workflows/ci.yml/badge.svg?branch=master)

# Academic integrity #

This plugin allows enabling academic integrity statement upon commencing moodle URLs. A notice appears in a modal pop up window.
Users must agree to the statement, otherwise they will be redirected to a related course page. 
Once agreed in a context, the statement won't be displayed again, unless reset manually by administrators.

## Versions and branches ##

| Moodle Version    |  Branch      | 
|-------------------|--------------|
| Moodle 3.9+       | master       | 

## Features ##
                                                      
* supports various Moodle activities;
* configurable notice message for each statement;
* configurable default status for each of the statement (enabled or disabled by default);                           
* capabilities to change a default state for each of the statements (integritystmt/PLUGIN_NAME:changedefault);
* capability to bypass statement notice (local/integrity:bypassnotice);
* a CLI script to reset user data for a course, an activity, a statement plugin, a user or all data in a system;


## Supported activities ##
 * Database
 * Forum
 * Open Forum
 * Quiz
 * Lesson
 * Workshop
 * Interactive Content (mod_hvp)
 * H5P (mod_h5pactivity)

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/local/integrity

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## Dev notes ##

Statements are implemented as subplugins. See one of the existing statements to figure out how to implement one that you require. 


# Crafted by Catalyst IT

This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

![Catalyst IT](/pix/catalyst-logo.png?raw=true)

# Contributing and Support

Issues, and pull requests using github are welcome and encouraged!

https://github.com/catalyst/moodle-local_integrity/issues

If you would like commercial support or would like to sponsor additional improvements
to this plugin please contact us:

https://www.catalyst-au.net/contact-us
