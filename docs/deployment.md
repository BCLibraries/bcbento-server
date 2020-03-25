To run a fresh deployment

    /apps/bcbento-server/deploy.sh
    

The deployment script takes a git branch name as an optional parameter. The branch must be available on GitHub.

    /apps/bcbento-server/deploy.sh fulltext-finder
    
## What happens in deployment?

The deployment script:

1. Makes a new timestamped release directory for the branch to deploy.
2. Clones the branch into the new release directory.
3. Updates the environment the new release directory to point to the existing environment variables and shared directories (cache, log, etc.).
4. Runs `composer` to install any dependencies.
5. Clears the cache.
6. Deletes any release directories that are more than 3 deployments old.
7. Sets the new release directory to be the current production directory.
8. Restarts `php-fpm` to activate the new release.