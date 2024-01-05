#!/bin/bash

SCRIPT=$(readlink -f $0)
ZIP=`dirname $SCRIPT`
VERSION=$(jq -r '.version' ./composer.json)

rm -f *.zip
if [ -d ./temp ]
then
    rm -rf ./temp
fi
mkdir -p ./temp/MappDigital/Cloud

cp ./composer.json ./temp/MappDigital/Cloud
cp ./registration.php ./temp/MappDigital/Cloud
cp ./CHANGELOG.md ./temp/MappDigital/Cloud
cp ./README.md ./temp/MappDigital/Cloud
cp ./LICENSE.txt ./temp/MappDigital/Cloud
cp -r ./Api ./temp/MappDigital/Cloud
cp -r ./Controller ./temp/MappDigital/Cloud
cp -r ./Enum ./temp/MappDigital/Cloud
cp -r ./Framework ./temp/MappDigital/Cloud
cp -r ./Observer ./temp/MappDigital/Cloud
cp -r ./Block ./temp/MappDigital/Cloud
cp -r ./Console ./temp/MappDigital/Cloud
cp -r ./Cron ./temp/MappDigital/Cloud
cp -r ./Helper ./temp/MappDigital/Cloud
cp -r ./Logger ./temp/MappDigital/Cloud
cp -r ./Model ./temp/MappDigital/Cloud
cp -r ./Plugin ./temp/MappDigital/Cloud
cp -r ./Setup ./temp/MappDigital/Cloud
cp -r ./etc ./temp/MappDigital/Cloud
cp -r ./view ./temp/MappDigital/Cloud

cd ./temp && zip -rq ../Mapp_Cloud_Magento2_$VERSION.zip ./MappDigital
cd ./MappDigital/Cloud && zip -rq ../../../Mapp_Cloud_Magento2_For_Marketplace_$VERSION.zip ./*

rm -rf $ZIP/temp