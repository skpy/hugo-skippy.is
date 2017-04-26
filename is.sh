#!/bin/bash
SOURCEDIR='/var/www/html/upload/'
DESTDIR='/home/skippy/skippy.is/'
RSYNCTARGET='skippy.is:/var/www/html/'

DIR=$(ls -A ${SOURCEDIR})
if [ -z "${DIR}" ]; then
  # empty directory; nothing to do
  exit 0;
fi

# We have files.  Source the twitter virtualenv so we can use it easily.
# https://github.com/sixohsix/twitter
source /home/skippy/twitter/bin/activate

for FILE in $DIR; do
  SOURCEFILE=${SOURCEDIR}${FILE}
  filename=$(basename "${FILE}")
  ext="${filename##*.}"
  if [ 'md' != ${ext} ]; then
    # not a markdown file.
    # sanity check: make sure this is an image
    /usr/bin/identify -format '%m' ${SOURCEFILE} &>/dev/null
    if [ $? -ne 0 ]; then
      # not an image.
      rm ${SOURCEFILE}
      continue;
    fi
    mv ${SOURCEFILE} ${DESTDIR}static/images/;
    chmod 644 ${DESTDIR}static/images/${FILE}
  else
    # TODO: do something sane if the filename is already used.
    if [ ! -f ${DESTDIR}content/${filename} ]; then
      # the file does not exist
      slug=$(grep '^permalink: ' ${SOURCEFILE} | cut -d ' ' -f 2-)
      title=$(grep '^title: ' ${SOURCEFILE} | cut -d ' ' -f 2-)
      mv ${SOURCEFILE} ${DESTDIR}content/;
      chmod 644 ${DESTDIR}content/${FILE}
      cd ${DESTDIR}
      /home/skippy/bin/hugo
      /usr/bin/rsync -aqz ${DESTDIR}public/ ${RSYNCTARGET}
      twitter set "skippy is ${title}\nhttps://skippy.is/${slug}/\n"
    fi
  fi
done
deactivate
