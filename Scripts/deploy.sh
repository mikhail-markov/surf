#!/usr/bin/env bash

# Stops The Flow if a the Command Before Failed
function exitOnFail {
    if [ $? -ne 0 ]; then
        echo "ERROR: $1"
        exit 1
    fi
}

# Help Screen
function usage {
    echo ""
    echo "deploy.sh expects a parameter"
    echo -e "usage:   deploy.sh <target>"

    echo -e "\nPossible targets:"
    for file in configurations/*.php; do
        target=$(basename $file)
        target=${target%%.*}
        echo -e $target
    done
    exit 1;
}

##
##  MAIN Program
##

# if no Option is given via Parameter ask.
target="$1"
if [ -z "$target" ]; then
    usage
else
    git pull && composer install && vendor/bin/surf deploy --configurationPath=./configurations "$target"
fi

