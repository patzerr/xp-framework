<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  uses(
    'unittest.TestListener',
    'io.streams.OutputStreamWriter',
    'lang.Runtime'
  );

  /**
   * Default listener - only shows details for failed tests.
   *
   * @purpose  TestListener
   */
  class XTermTitleUpdateListener extends Object implements TestListener {
    private $out= NULL;

    /**
     * Constructor
     *
     * @param   io.streams.OutputStreamWriter out
     */
    public function __construct(OutputStreamWriter $out) {
      $this->out= $out;
    }

    /**
     * Called when a test case starts.
     *
     * @param   unittest.TestCase failure
     */
    public function testStarted(TestCase $case) {
    }

    /**
     * Called when a test fails.
     *
     * @param   unittest.TestFailure failure
     */
    public function testFailed(TestFailure $failure) {
    }

    /**
     * Called when a test errors.
     *
     * @param   unittest.TestError error
     */
    public function testError(TestError $error) {
    }

    /**
     * Called when a test raises warnings.
     *
     * @param   unittest.TestWarning warning
     */
    public function testWarning(TestWarning $warning) {
    }
    
    /**
     * Called when a test finished successfully.
     *
     * @param   unittest.TestSuccess success
     */
    public function testSucceeded(TestSuccess $success) {
    }
    
    /**
     * Called when a test is not run because it is skipped due to a 
     * failed prerequisite.
     *
     * @param   unittest.TestSkipped skipped
     */
    public function testSkipped(TestSkipped $skipped) {
    }

    /**
     * Called when a test is not run because it has been ignored by using
     * the @ignore annotation.
     *
     * @param   unittest.TestSkipped ignore
     */
    public function testNotRun(TestSkipped $ignore) {
    }

    /**
     * Called when a test run starts.
     *
     * @param   unittest.TestSuite suite
     */
    public function testRunStarted(TestSuite $suite) {
    }
    
    /**
     * Called when a test run finishes.
     *
     * @param   unittest.TestSuite suite
     * @param   unittest.TestResult result
     */
    public function testRunFinished(TestSuite $suite, TestResult $result) {
      $this->out->writeLinef(
        "\033]2;%s: %d/%d run (%d skipped), %d succeeded, %d failed\007",
        $fail ? 'FAIL' : 'OK',
        $result->runCount(),
        $result->count(),
        $result->skipCount(),
        $result->successCount(),
        $result->failureCount()
      );
    }
  }
?>
