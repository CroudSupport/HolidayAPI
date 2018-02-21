#!/bin/bash

cd $WEBAPP_ROOT

# Destroy ENV
rm .env*

# Re-create ENV
touch .env

# Reset IFS
unset IFS

# Get a list of all environment variables and dump into the .env file
for var in $(compgen -e); do
   echo "$var=\"${!var}\"" >> .env
done
