#/bin/bash

# Exit on errors
set -e

NAME="storytlr"

if [ -z $1 ]
then
  VERSION=`git describe --long --match "release*dev" | awk -F"-" '{print $2}'`
else
  VERSION=$1
fi

if [ -z $2 ] 
then
  RELEASE=`git describe --long --match "release*dev" | awk -F"-" '{print $3}'`
else
  RELEASE=$2
fi

COMMIT=`git describe --long --match "release*dev" | awk -F"-" '{print $4}'`

cd `dirname $0`
cd ..

sed "s/#VERSION#/${VERSION}/g" build/rpmbuild/${NAME}.spec.template > build/rpmbuild/${NAME}.spec
sed -i "s/#RELEASE#/${RELEASE}/g" build/rpmbuild/${NAME}.spec
sed -i "s/#COMMIT#/${COMMIT}/g" build/rpmbuild/${NAME}.spec

tar -cvzf $HOME/rpmbuild/SOURCES/${NAME}-${VERSION}-${RELEASE}.tar.gz --exclude .git --exclude scripts .
rpmbuild -ba build/rpmbuild/${NAME}.spec
