<?php

use Go\Instrument\ClassLoading\AopComposerLoader;
use Go\Instrument\Transformer\FilterInjectorTransformer;
use Doctrine\Common\Annotations\AnnotationRegistry;

class AopSilverStripeLoader extends AopComposerLoader {

	protected $tooComplex = array(
		'ViewableData_Debugger',
		'SS_HTTPResponse',
		'LeftAndMain_TreeNode',
		'i18n',
		'DB'
	);

	/**
	 * Store the original class loader.
	 */
	public function __construct(SS_ClassLoader $original) {
		$this->original = $original;
	}

	/**
	 * The original AopComposerLoader does not know how to work with SilverStripe's autoloader.
	 * Provide a custom initialisation so we can hook Go! in there as well.
	 */
	public static function init() {
		$loaders = spl_autoload_functions();

		foreach ($loaders as &$loader) {
			$loaderToUnregister = $loader;

			// Only hook onto SilverStripe's SS_ClassLoader.
			if (is_array($loader) && ($loader[0] instanceof SS_ClassLoader)) {
				$originalLoader = $loader[0];

				// Configure library loader for doctrine annotation loader
				AnnotationRegistry::registerLoader(function ($class) use ($originalLoader) {
					$originalLoader->loadClass($class);

					return class_exists($class, false);
				});
				$loader[0] = new AopSilverStripeLoader($loader[0]);
			}
			spl_autoload_unregister($loaderToUnregister);
		}
		unset($loader);

		foreach ($loaders as $loader) {
			spl_autoload_register($loader);
		}
	}

	/**
	 * Hijack SilverStripe's autoloader, so we can get rid of the "require_once" found there, which prevents
	 * Go! from being able to hook into the class. Redirect the loading into the Go! framework instead.
	 * Note we are still using SilverStripe manifest to find the files, so the fundamental logic stays the same.
	 */
	public function loadClass($class) {
		if ($path = $this->original->getItemPath($class)) {

			// Ignore some SS classes that are to complex to transform.
			$isIgnored = in_array($class, $this->tooComplex);

			// Do not attempt to transform internal Go files because we will end up with inception.
			if (!$isIgnored) foreach ($this->internalNamespaces as $ns) {
				if (strpos($class, $ns) === 0) {
					$isIgnored = true;
					break;
				}
			}

			if ($isIgnored) {
				require_once $path;
			} else {
				require_once FilterInjectorTransformer::rewrite($path);
			}
		}

		return $path;
	}

}
