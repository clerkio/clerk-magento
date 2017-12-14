clerk.io for Magento
====================

[clerk.io](http://www.clerk.io) is a international tech-startup based in
Copenhagen targeting the eCommerce industry. We help webshops sell more
through an intelligent search function and targeted product recommendations.

This extension replaces the default search functionality in Magento with
a typo-tolerant, fast & relevant search experience and enables product
recommendations.

See features and benefits of [clerk.io for
Magento](https://help.clerk.io/using-clerk-io-on-magento-1/getting-started/getting-started-on-magento-1).

![Latest version](https://img.shields.io/badge/latest-1.2.3-green.svg)
![Magento 1.9.x.x](https://img.shields.io/badge/magento-1.9-blue.svg)
![PHP >= 5.3](https://img.shields.io/badge/php-%3E=5.3-green.svg)


Installation
--------------
To setup this module, you'll need an Clerk account.

  1. Contact Clerk and get your credentials for
     [my.clerk.io](http://my.clerk.io).
  2. Download the latest packaged Community Extension from the releases
     folder.
  3. Install it on your Magento instance through magento connect, you have to
     relogin after install.
  4. Configure module, to your needs, find help at [help.clerk.io for
     Magento](https://help.clerk.io/using-clerk-io-on-magento-1/getting-started/getting-started-on-magento-1).


Contribute to the Extension
---------------------------
Everybody is welcome to contribute to this extension. Just send a pull request with your changes ;)

There is a docker dev environment available, 
https://clerkpublic.s3.amazonaws.com/magento-devenv.mov

#### Docker

The easiest way to setup your development environment is to use [Docker](https://www.docker.com/). If you're a Mac user, use [Docker for Mac](https://docs.docker.com/engine/installation/mac/) to run Docker containers.

#### Setup the Docker instance

Just run the following script to setup a running Magento 1.9.2 instance with some sample data & the Clerk extension installed:

```sh
$ ./dev/restart.sh -b http://`docker ip`/
$ # The default value for -b is 127.0.0.1.
```

#### Administration panel

Administration login is `admin` with password `magentorocks1` and you can access it from `http://[docker ip]/admin`.

#### phpMyAdmin

A phpMyAdmin instance is available from `http://[docker ip]/phpmyadmin`

#### Shell

You can execute a shell inside the container with the following command:

```sh
$ docker exec -i -t clerk-magento /bin/bash
```
