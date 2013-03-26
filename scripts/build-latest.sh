#!/bin/sh -e

NAME=$1
PROJECT=$2
REPO=$3

cd $PROJECT
git checkout master
LAST_COMMIT=`git describe --long --match "release*dev" | awk -F"-" '{print $4}'`

git pull
NOW_COMMIT=`git describe --long --match "release*dev" | awk -F"-" '{print $4}'`

if [ "$LAST_COMMIT" != "$NOW_COMMIT" ]; then
  echo "A new commit is detected, repackaging"
  cd $PROJECT
  VERSION=`git describe --long --match "release*dev" | awk -F"-" '{print $2}'`
  RELEASE=`git describe --long --match "release*dev" | awk -F"-" '{print $3}'`
  ./scripts/build-rpm.sh
  cp $HOME/rpmbuild/RPMS/noarch/${NAME}-${VERSION}-${RELEASE}.noarch.rpm $REPO/epel/6/x86_64/
  cp $HOME/rpmbuild/RPMS/noarch/${NAME}-${VERSION}-${RELEASE}.noarch.rpm $REPO/epel/6/i386/
  createrepo $REPO/epel/6/x86_64/
  createrepo $REPO/epel/6/i386/
  rm $HOME/rpmbuild/RPMS/noarch/${NAME}-${VERSION}-${RELEASE}.noarch.rpm 
fi

