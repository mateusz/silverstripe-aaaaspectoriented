<?php
/**
 * This is an ugly solution to the problem of augmenting the autoloaders for the Go! AOP framework.
 * We have to initialise the Go! kernel *after* the autoloaders have been registered (which is done in the Core.php)
 * but before the first class is loaded - otherwise Go! will have no chance to inject aspects.
 *
 * Core.php will load the _config.php files shortly after the autoloaders are configured, so this seems like a good
 * (read: easy) solution for this.
 */

AopSilverStripeKernel::begin();
