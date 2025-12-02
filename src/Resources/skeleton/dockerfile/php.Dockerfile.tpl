<?= $fromStatement ?>

<?php if(!empty($extensionsSelected)): ?>
RUN install-php-extensions  <?= $extensionsSelected ?>
<?php endif; ?>

## Custom code Here ... ##
## End Custom code ##

FROM stage_dev AS stage_prod
COPY . /var/www/html
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

