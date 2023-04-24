#
deploy:
	rsync -CFavz \
		--delete-after \
		./ \
		${DEPLOY_USER}@${DEPLOY_HOST}:/srv/i.gslin.com/
