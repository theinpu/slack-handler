<?php


use bc\Monolog\SlackWebHookHandler;

class SlackWebHookHandlerTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    // tests
    public function testLog()
    {
        $logger = new \Monolog\Logger("Test", [
            new SlackWebHookHandler("https://hooks.slack.com/services/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")
        ]);

        $logger->addDebug("debug message");
    }
}