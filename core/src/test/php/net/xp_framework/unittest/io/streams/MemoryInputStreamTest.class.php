<?php namespace net\xp_framework\unittest\io\streams;

use unittest\TestCase;
use io\streams\MemoryInputStream;


/**
 * Unit tests for streams API
 *
 * @see      xp://io.streams.InputStream
 * @purpose  Unit test
 */
class MemoryInputStreamTest extends TestCase {
  const BUFFER= 'Hello World, how are you doing?';

  protected $in= null;

  /**
   * Setup method. Creates the fixture.
   *
   */
  public function setUp() {
    $this->in= new MemoryInputStream(self::BUFFER);
  }

  /**
   * Test reading all
   *
   */
  #[@test]
  public function readAll() {
    $this->assertEquals(self::BUFFER, $this->in->read(strlen(self::BUFFER)));
    $this->assertEquals(0, $this->in->available());
  }

  /**
   * Test reading a five byte chunk
   *
   */
  #[@test]
  public function readChunk() {
    $this->assertEquals('Hello', $this->in->read(5));
    $this->assertEquals(strlen(self::BUFFER)- 5, $this->in->available());
  }
  
  /**
   * Test closing a stream twice has no effect.
   *
   * @see   xp://lang.Closeable#close
   */
  #[@test]
  public function closingTwice() {
    $this->in->close();
    $this->in->close();
  }
}
