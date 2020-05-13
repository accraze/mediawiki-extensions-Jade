Jade
====
A Mediawiki extension for human refutation and review of [ORES](https://mediawiki.org/wiki/ORES) scoring. A robust false-positive and feedback gathering system for auditing our ML models.

* **Documentation:** https://www.mediawiki.org/wiki/Extension:Jade
* **License:** GPL-3.0-or-later

## Requirements

### System-level dependencies
- **Mediawiki v1.35+**
- **PHP v7+**
- **Node.js v10**

## Local Development

First install MW Vagrant by following directions here:
https://www.mediawiki.org/wiki/MediaWiki-Vagrant

**IMPORTANT!! --** you must update nodejs to v10+ -- do this before enabling the Jade role.

```
vagrant hiera npm::node_version: 10 && vagrant provision
```

Next enable and provision the 'jade' role

```
vagrant roles enable jade --provision
```

Now you can start vagrant and ssh into the machine to confirm Jade is installed:

```
vagrant up
vagrant ssh
cd /vagrant/mediawiki/extensions/Jade/
```

Once you are in the Jade directory, make sure to install all the JS
dependencies:

```
npm install
```

The extension should now be installed with the newest version available.
Now try to create a new Jade proposal using API Sandbox:

http://dev.wiki.local.wmftest.net:8080/wiki/Special:ApiSandbox#action=jadecreateandendorse&title=Jade:Diff/1234556&facet=editquality&labeldata=%7B%22damaging%22:false,%20%22goodfaith%22:true%7D&notes=this-makes-more-sense&endorsementorigin=mw-api-sandbox&comment=this-is-a-test&formatversion=2

If successful, you should now be able to view your new Jade entity page:
http://dev.wiki.local.wmftest.net:8080/wiki/Jade:Diff/1234556

### Running tests
There are currently three sets of tests you can run locally.

#### PHPUnit tests:
These need to be run from the root mediawiki directory:

```
cd /vagrant/mediawiki
sudo -u www-data env "PHP_IDE_CONFIG=serverName=mwvagrant" "CIRRUS_REBUILD_FIXTURES=yes" "XDEBUG_CONFIG=idekey=netbeans-xdebug" php tests/phpunit/phpunit.php --wiki=wiki --stop-on-failure --stop-on-error extensions/Jade/tests/phpunit/
```

#### PHP Composer tests (style/static analysis/etc):
These need to be run from the root Jade directory:

```
cd /vagrant/mediawiki/extensions/Jade
composer test
```

#### JS tests:
These need to be run from the root Jade directory:

```
cd /vagrant/mediawiki/extensions/Jade
grunt
```
