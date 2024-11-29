#!/bin/bash
echo "Deploying to Dev"

# Change to site folder.
cd /var/sites/uscourts

# DB Backup.
docker exec uscourts-php robo backup:database

# Discart other changes.
git checkout .
git clean -fd

# Deploy.
git pull origin master
docker exec uscourts-php robo app:update
docker exec uscourts-php robo app:admin-pass
docker exec uscourts-php vendor/bin/drush content:import ../content_default/test