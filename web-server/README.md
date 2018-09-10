###  Installation instructions
* going to need php7+

#### OSX
* To install php71
    * https://developerjack.com/blog/2016/installing-php71-with-homebrew/ (OSX)
```bash
# If you don't have xcode's command line tools up to date
xcode-select --install
# install homebrew
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)")
```

#### General

```bash


# go to web-server directory
cd <path>/web-server

# You are going to need to checkout the submodule as-core-viz
git submodule update --init --recursive

# downloads the current version of composer
# make sure you are using PHP7+
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# use composer to create a new symfony working directory
composer create-project symfony/skeleton tmp
find ./tmp -name ".git*" -prune -exec rm -rf {} \;
cp -rnp tmp/ .
rm -r tmp

# Use npm to download  http://flag-icon-css.lip.is/
npm install flag-icon-css
cp -r node_modules/flag-icon-css/flags public/.
cp node_modules/flag-icon-css/css/flag-icon.css public/css/.
rm -r node_modules

# install the nessary libraries
composer require twig
composer require annotations
composer require --dev profiler 
composer require asset
composer require symfony/process

# download and place the nessary bootstrap files
wget https://github.com/twbs/bootstrap/releases/download/v4.0.0-beta.3/bootstrap-4.0.0-beta.3-dist.zip
unzip bootstrap-4.0.0-beta.3-dist.zip
cp -rnp css js public/
rm -r css js
rm bootstrap-4.0.0-beta.3-dist.zip

# Used for tooltips; *.map files are used by the respective library
wget https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js -O public/js/popper.min.js
wget https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js.map -O public/js/popper.min.js.map
wget https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js -O public/js/jquery.min.js
wget https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.map -O public/js/jquery.min.map

# Update .env to point to a running restful server
# -- edit .env "RESTFUL_DATABASE_URL=http://tonic.caida.org:3000"
cp .env.dist .env

# Go into that directory and set permissions for the logs etc
php -S 127.0.0.1:8000 -t public
```

### Constants / Configuration
* https://symfony.com/doc/current/configuration/external_parameters.html#config-env-vars
* https://symfony.com/doc/current/templating/global_variables.html

```bash
# .env
RESTFUL_DATABASE_URL=localhost:3000

# config/services.yaml
parameters:
    env(RESTFUL_DATABASE_URL): localhost

# config/packages/twig.yaml
twig:
    globals:
        RESTFUL_DATABASE_URL: '%env(RESTFUL_DATABASE_URL)%'

# inside a twig file
{{RESTFUL_DATABASE_URL}}
```

### Deployment
* https://symfony.com/doc/current/deployment.html

```bash
# After you have updated scripts you need to clear the cache
./prod.sh
```

###  Useful bits
* I found it useful to install vim-twig (https://github.com/lumiliet/vim-twig)

