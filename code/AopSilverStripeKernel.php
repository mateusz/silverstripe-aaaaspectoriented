<?php
use Go\Core\AspectKernel;
use Go\Core\AspectContainer;

class AopSilverStripeKernel extends AspectKernel implements Flushable {

	public static function begin() {
		// Initialise Go!
		$applicationAspectKernel = self::getInstance();
		$applicationAspectKernel->init(array(
			'debug' => false,
			'cacheDir'  => TEMP_FOLDER . '/aop'
		));

		// Go! doesn't know how to work with SilverStripe's autoloader, so initialise that one on our own.
		AopSilverStripeLoader::init();
	}

	protected function configureAop(AspectContainer $container) {
		foreach(ClassInfo::subclassesFor('SilverStripeAspect') as $class) {
			if ($class==='SilverStripeAspect') continue;
			$container->registerAspect(new $class());
		}
	}

	public static function flush() {
		$transformationCache = TEMP_FOLDER . '/aop';
		`rm -fr $transformationCache`;
	}
}

