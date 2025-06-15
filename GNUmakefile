#
.DEFAULT_GOAL:=	lint
.PHONY:=	deploy lint test

#
-include GNUmakefile.local

#
deploy:: lint
	rsync -CFavz \
		--delete-after \
		./ \
		${DEPLOY_USER}@${DEPLOY_HOST}:/srv/i.gslin.com/

lint::
	phpcs --standard=PSR2 public/

test::
	php -l public/upload.php
