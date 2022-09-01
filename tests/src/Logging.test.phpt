<?php

namespace OndraKoupil\Csob;

require '../bootstrap.php';

use \Tester\Assert;
use \Tester\TestCase;

use \OndraKoupil\Tools\Files;

class LoggingTestCase extends TestCase {

	function testLoggingToFile() {

		$config = require(__DIR__ . "/../dummy-config.php");

		$dir = $this->getTempDir();
		$logFile = $dir."/log.txt";

		$client = new Client($config, $logFile);

		// Log should be created and be empty
		Assert::true(file_exists($logFile));
		Assert::equal(0, filesize($logFile));
		Assert::true(is_writable($logFile));

		// it should contain the message...
		$message = "Hello world!";
		$client->writeToLog($message);
		$logContents = file_get_contents($logFile);
		Assert::contains($message, $logContents);

		// ...and timestamp
		$year = date("Y");
		Assert::contains($year, $logContents);

	}

	function testLoggingWithCallback() {

		$config = require(__DIR__ . "/../dummy-config.php");

		$messages = array();

		$logger = function($message) use (&$messages) {
			$messages[] = $message;
		};

		$client = new Client($config, $logger);

		Assert::equal(0, count($messages));

		$message = "Hello world!";
		$client->writeToLog($message);
		$client->writeToTraceLog($message);

		Assert::equal(1, count($messages));
		Assert::equal($message, $messages[0]);

		$client->setLog(null);

		$client->writeToLog($message);
		Assert::equal(1, count($messages));

		$message2 = "Hello sun!";
		$client->setTraceLog($logger);
		$client->writeToLog($message2);
		$client->writeToTraceLog($message2);

		Assert::equal(2, count($messages));
		Assert::equal($message, $messages[0]);
		Assert::equal($message2, $messages[1]);

	}

	function testLoggingClient() {

		$config = require(__DIR__ . "/../dummy-config.php");

		$dir = $this->getTempDir();
		$logFile = $dir."/log.txt";
		$traceLogFile = $dir."/tracelog.txt";

		$client = new Client($config);
		$client->setLog($logFile);
		$client->setTraceLog($traceLogFile);

		$dummyPayId = "abcde12345abcde";
		$client->getPaymentProcessUrl($dummyPayId);

		$logContents = file_get_contents($logFile);
		$traceLogContents = file_get_contents($traceLogFile);

		Assert::contains($dummyPayId, $logContents);
		Assert::contains($dummyPayId, $traceLogContents);


	}


    private $tempDir=false;

	function tearDown() {
		parent::tearDown();
		if ($this->tempDir) {
			try {
				Files::removeDir($this->tempDir);
				$this->tempDir=false;
			} catch (\Exception $e) {
				throw new \Tester\TestCaseException("Could not remove temp directory $this->tempDir, there might be some files left inside.", 1, $e);
			}
		}
	}


	/**
	 * Vytvoří dočasný adresář. Nevolej tuto metodu, ale getTempDir().
	 * @return string
	 * @throws \Tester\TestCaseException
	 */
	function createTempDir() {
		if ($this->tempDir) {
			throw new \Tester\TestCaseException("Temp directory already exists, can not create another one.");
		}
		$dir=uniqid("test");
		if (!defined("TMP_TEST_DIR")) throw new \Tester\TestCaseException("Constant TMP_TEST_DIR was not set in test bootstrap! Please define it with path to some base temp dir in which FilesTestCase can create its own temp directories.");
		Files::mkdir(TMP_TEST_DIR."/".$dir);
		$this->tempDir=TMP_TEST_DIR."/".$dir;
		return TMP_TEST_DIR."/".$dir;
	}

	/**
	 * Vrátí cestu k dočasnému adresáři, do kterého si test může ukládat, cokoliv chce.
	 * Pokud zatm žádný testovací adresář neexistuje, vytvoří ho.
	 * @return string
	 */
	function getTempDir() {
		if ($this->tempDir) {
			return $this->tempDir;
		}
		return $this->createTempDir();
	}

	function setUp() {
		parent::setUp();
		$this->tempDir=false;
	}
}

$case = new LoggingTestCase();
$case->run();
