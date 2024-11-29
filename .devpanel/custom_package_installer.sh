#!/bin/bash
# ---------------------------------------------------------------------
# Copyright (C) 2023 DevPanel
# You can install any service here to support your project
# Please make sure you run apt update before install any packages
# Example:
# - sudo apt-get update
# - sudo apt-get install nano
#
# ----------------------------------------------------------------------

# sudo apt-get update
# sudo apt-get install -y nano

code-server --install-extension ikappas.phpcs  --user-data-dir=$APP_ROOT/.vscode
code-server --install-extension andrewdavidblum.drupal-smart-snippets --user-data-dir=$APP_ROOT/.vscode
