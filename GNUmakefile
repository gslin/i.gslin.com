#
-include GNUmakefile.local

#
deploy:
	rsync -CFavz \
		--delete-after \
		./ \
		${DEPLOY_USER}@${DEPLOY_HOST}:/srv/i.gslin.com/

test:
	php -l public/upload.php
