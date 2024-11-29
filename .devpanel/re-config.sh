#!/bin/bash
# ---------------------------------------------------------------------
# Copyright (C) 2021 DevPanel
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation version 3 of the
# License.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# For GNU Affero General Public License see <https://www.gnu.org/licenses/>.
# ----------------------------------------------------------------------

#== If webRoot has not been difined, we will set appRoot to webRoot
if [[ ! -n "$WEB_ROOT" ]]; then
  export WEB_ROOT=$APP_ROOT
fi

STATIC_FILES_PATH="$WEB_ROOT/sites/default/files/"
SETTINGS_FILES_PATH="$WEB_ROOT/sites/default/settings.php"
OVERWRITE_SETTING="$APP_ROOT/.devpanel/.devpanel-drupal-overwrite-settings"


#Create static directory
if [ ! -d "$STATIC_PATH" ]; then
  mkdir -p $STATIC_FILES_PATH
fi


#== Composer install.
if [[ -f "$APP_ROOT/composer.json" ]]; then
  cd $APP_ROOT && composer install
fi
if [[ -f "$WEB_ROOT/composer.json" ]]; then
  cd $WEB_ROOT && composer install
fi

#== Install drush locally
echo "Install drush locally ..."
composer require drush/drush


cd $WEB_ROOT && git submodule update --init --recursive

#== Create settings files
# @link: https://www.drupal.org/docs/7/install/step-3-create-settingsphp-and-the-files-directory
if [[ ! -f "$SETTINGS_FILES_PATH" ]]; then
  sudo cp $APP_ROOT/.devpanel/drupal-settings.php $SETTINGS_FILES_PATH
fi

#== Generate hash salt
echo 'Generate hash salt ...'
DRUPAL_HASH_SALT=$(openssl rand -hex 32);
sudo sed -i -e "s/^\$settings\['hash_salt'\].*/\$settings\['hash_salt'\] = '$DRUPAL_HASH_SALT';/g" $SETTINGS_FILES_PATH


# #Securing file permissions and ownership
# #https://www.drupal.org/docs/security-in-drupal/securing-file-permissions-and-ownership
[[ ! -d $STATIC_FILES_PATH ]] && sudo mkdir --mode 775 $STATIC_FILES_PATH || sudo chmod 775 -R $STATIC_FILES_PATH

#== Extract static files
if [[ $(drush status bootstrap) == '' ]]; then
  if [[ -f "$APP_ROOT/.devpanel/dumps/files.tgz" ]]; then
    echo  'Extract static files ...'
    sudo mkdir -p $STATIC_FILES_PATH
    sudo tar xzf "$APP_ROOT/.devpanel/dumps/files.tgz" -C $STATIC_FILES_PATH
  fi

  #== Import mysql files
  if [[ -f "$APP_ROOT/.devpanel/dumps/db.sql.tgz" ]]; then
    echo  'Extract mysql files ...'
    SQLFILE=$(tar tzf $APP_ROOT/.devpanel/dumps/db.sql.tgz)
    tar xzf "$APP_ROOT/.devpanel/dumps/db.sql.tgz" -C /tmp/
    mysql -h$DB_HOST -P$DB_PORT -u$DB_USER -p$DB_PASSWORD $DB_NAME < /tmp/$SQLFILE
    rm /tmp/$SQLFILE
  fi
fi

#== Update permission
echo 'Update permission ....'
drush cr
sudo chown -R www-data:www-data $STATIC_FILES_PATH
sudo chown www:www-data $SETTINGS_FILES_PATH
sudo chmod 664 $SETTINGS_FILES_PATH
sudo rm -rf lost+found/
