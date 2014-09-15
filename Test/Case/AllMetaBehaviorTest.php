<?php
/**
 * All MetaBehavior plugin tests
 */
class AllMetaBehaviorTest extends CakeTestCase {

/**
 * Suite define the tests for this plugin
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All MetaBehavior test');

		$path = CakePlugin::path('MetaBehavior') . 'Test' . DS . 'Case' . DS;
		$suite->addTestDirectoryRecursive($path);

		return $suite;
	}

}
