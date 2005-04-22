#!/bin/sh
#
# WTP setup/installation tool
#
#

VERSION=%%VERSION
DEFAULT_HOSTNAME=`dnsdomainname -f`
DEFAULT_PREFDIR=/var/wtp/
DEFAULT_SCRIPTDIR=/var/www/wtp/
DEFAULT_WEBUSER=www-data
DEFAULT_WEBGROUP=www-data

#--------------------------------------------
# Show information
#--------------------------------------------
echo
echo WTP v$VERSION setup
echo ------------------------
echo
echo WTP is copyright by Ferry Boender. Released under the General 
echo Public License \(GPL\). See the COPYING file for more information.
echo
echo This setup script will install WTP on your system. In order to
echo be of any help with installing, this script will have to ask you a few
echo questions. The default values are between [ and ]. If you would like to
echo use these default values, just press return at the prompt.
echo 
echo Make sure you read the README file.
echo If you are upgrading WTP, please read the UPGRADING file.
echo
echo --

#-------------------------
# check if user is root
#-------------------------
if [ `id -u` != 0 ]; then 
	echo "In order to properly install WTP you will have to be root. You are"
	echo "not root. You may proceed with this setup script, but you will not be able"
	echo "to use the default installation values. To stop this script now, press"
	echo "ctrl-c"
	echo
	echo -n "Press enter to continue, or ctrl-c to stop: "
	read
	echo "--"
fi

#---------------------------------------
# get hostname
#---------------------------------------
echo "What is the domainname to which WTP will connect to? If you leave this"
echo "empty, users will be able to use WTP to connect to any host they want to."
echo
echo "You may specify multiple hostnames by seperating them with a ';'. Non-default"
echo "ports can be specified per host by separating the hostname and port with a ':'"
echo 
read -p"[$DEFAULT_HOSTNAME] " HOSTNAME
echo "--"

if [ -z $HOSTNAME ]; then HOSTNAME=$DEFAULT_HOSTNAME; fi

#---------------------------------------
# get preferences installation path
#---------------------------------------
echo "WTP needs a location on this server to keep it's users configuration"
echo "files, like bookmarks. Where would you like them to be stored?"
echo
read -p"[$DEFAULT_PREFDIR] " PREFDIR
echo "--"

if [ -z $PREFDIR ]; then PREFDIR=$DEFAULT_PREFDIR; fi

#----------------------------------------
# get webserver username
#----------------------------------------
echo "The webserver needs to be able to write to the WTP preferences"
echo "directory specified above, What is the USERNAME of your webserver?"
echo
read -p"[$DEFAULT_WEBUSER] " WEBUSER
echo "--"

if [ -z $WEBUSER ]; then WEBUSER=$DEFAULT_WEBUSER; fi

#----------------------------------------
# get webserver groupname
#----------------------------------------
echo "The webserver needs to be able to write to the WTP preferences"
echo "directory specified above, What is the GROUPNAME of your webserver?"
echo
read -p"[$DEFAULT_WEBGROUP] " WEBGROUP
echo "--"

if [ -z $WEBGROUP ]; then WEBGROUP=$DEFAULT_WEBGROUP; fi

#----------------------------------------
# get wtp installation path
#----------------------------------------
echo "The WTP PHP script needs to be placed in a directory which is"
echo "accessible by your webserver. Where would you like it to be placed?"
echo "(The path should be appended with a 'wtp/' directory, because wtp"
echo "consists out of multiple files)"
echo
read -p"[$DEFAULT_SCRIPTDIR] " SCRIPTDIR
echo "--"

if [ -z $SCRIPTDIR ]; then SCRIPTDIR=$DEFAULT_SCRIPTDIR; fi

#---------------------------------------
# strip leading and trailing slashes
#---------------------------------------
PREFDIR=`echo $PREFDIR | sed -e 's/^\///;s/\/$//'`
SCRIPTDIR=`echo $SCRIPTDIR | sed -e 's/^\///;s/\/$//'`
PREFDIR=/$PREFDIR
SCRIPTDIR=/$SCRIPTDIR

#--------------------------------------
# Last chance
#--------------------------------------
echo "You have entered the following information:"
echo
echo "  Connect to domainname(s): $HOSTNAME"
echo "  User preferences path:    $PREFDIR/"
echo "  Webserver username:       $WEBUSER"
echo "  Webserver groupname:      $WEBGROUP"
echo "  WTP PHP script path:      $SCRIPTDIR/(wtp.php)"
echo
echo -n "Press enter to continue, or ctrl-c to stop: "
echo
read
echo "--"

echo "Proceeding with installation."
echo

#-----------------------------------------
# Prepare wtp.php for user defined vars
#-----------------------------------------
echo "Preparing wtp.php for new settings"
cat wtp.php | sed "s,^\$REF001.*,\$Hosts=\"$HOSTNAME\";,;s,^\$REF002.*,\$PreferenceDir=\"$PREFDIR/\";,;" > temp.wtp.php

#-----------------------------------------
# Install it
#-----------------------------------------

echo "Creating $PREFDIR/"; mkdir -p $PREFDIR/
echo "Creating $SCRIPTDIR/"; mkdir -p $SCRIPTDIR/
echo "Copying wtp.php to $SCRIPTDIR/"; mv temp.wtp.php $SCRIPTDIR/wtp.php
echo "Copying images/ dir to $SCRIPTDIR/"; cp images $SCRIPTDIR -R

#------------------------------------------
# Set some rights
#------------------------------------------

echo "Setting rights";
chmod 750 $PREFDIR/
chmod 750 $SCRIPTDIR/
chmod 644 $SCRIPTDIR/wtp.php
chmod 750 $SCRIPTDIR/images
chmod 644 $SCRIPTDIR/images/*
chown $WEBUSER:$WEBGROUP $PREFDIR/
chown $WEBUSER:$WEBGROUP $SCRIPTDIR/
chown $WEBUSER:$WEBGROUP $SCRIPTDIR/wtp.php
chown $WEBUSER:$WEBGROUP $SCRIPTDIR/images
chown $WEBUSER:$WEBGROUP $SCRIPTDIR/images/*

#---------------------------------------
# Write some info about installation 
#---------------------------------------
echo version=$VERSION > $PREFDIR/wtp.info

echo
echo "Installation is complete.."
echo
