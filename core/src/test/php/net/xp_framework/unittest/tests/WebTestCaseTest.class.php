<?php namespace net\xp_framework\unittest\tests;

use unittest\TestCase;
use unittest\web\WebTestCase;
use io\streams\MemoryInputStream;

/**
 * WebTestCase tests
 *
 * @see   xp://unittest.web.WebTestCase
 */
class WebTestCaseTest extends TestCase {
  protected $fixture= null;

  /**
   * Sets up test case
   */
  public function setUp() {
    $this->fixture= newinstance('unittest.web.WebTestCase', array($this->name), '{
      protected function getConnection($url= NULL) {
        return new HttpConnection("http://localhost/");
      }
      
      protected function doRequest($method, $params) {
        return $this->response;
      }
      
      public function respondWith($status, $headers= array(), $body= "") {
        $headers[]= "Content-Length: ".strlen($body);
        $this->response= new HttpResponse(new MemoryInputStream(sprintf(
          "HTTP/1.0 %d Message\r\n%s\r\n\r\n%s",
          $status,
          implode("\r\n", $headers),
          $body
        )));
      }
    }');
  }

  /**
   * Assertion helper
   *
   * @param   string $action
   * @param   string $method
   * @param   unittest.web.Form form
   * @throws  unittest.AssertionFailedError
   */
  protected function assertForm($action, $method, $form) {
    $this->assertClass($form, 'unittest.web.Form');
    $this->assertEquals($action, $form->getAction());
    $this->assertEquals($method, $form->getMethod());
  }

  /**
   * Returns the form used for testing below
   *
   * @return  string
   */
  protected function formFixture() {
    return trim('
      <html>
        <head>
          <title>Enter your name</title>
        </head>
        <body>
          <form>
            <input type="text" name="first"/>
            <input type="text" name="initial" value=""/>
            <input type="text" name="last" value="Tester"/>
            <input type="text" name="uber" value="�bercoder"/>

            <hr/>
            <select name="gender">
              <option value="-">(select one)</option>
              <option value="M">male</option>
              <option value="F">female</option>
              <option value="U">�berwoman</option>
            </select>

            <hr/>
            <select name="payment">
              <option value="V">Visa-Card</option>
              <option value="M">Master-Card</option>
              <option value="C" selected>Cheque</option>
            </select>

            <hr/>
            <textarea name="comments">(Comments)</textarea>

            <hr/>
            <textarea name="umlauts">�bercoder</textarea>
          </form>
        </body>
      </html>
    ');
  }

  #[@test]
  public function emptyDocument() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK);

    $this->fixture->beginAt('/');
    $this->fixture->assertStatus(\peer\http\HttpConstants::STATUS_OK);
    $this->fixture->assertHeader('Content-Length', '0');
  }

  #[@test]
  public function contentType() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array('Content-Type: text/html'));

    $this->fixture->beginAt('/');
    $this->fixture->assertContentType('text/html');
  }

  #[@test]
  public function contentTypeWithCharset() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array('Content-Type: text/xml; charset=utf-8'));

    $this->fixture->beginAt('/');
    $this->fixture->assertContentType('text/xml; charset=utf-8');
  }

  #[@test]
  public function errorDocument() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_NOT_FOUND, array(), trim('
      <html>
        <head>
          <title>Not found</title>
        </head>
        <body>
          <h1>404: The file you requested was not found</h1>
        </body>
      </html>
    '));

    $this->fixture->beginAt('/');
    $this->fixture->assertStatus(\peer\http\HttpConstants::STATUS_NOT_FOUND);
    $this->fixture->assertTitleEquals('Not found');
    $this->fixture->assertTextPresent('404: The file you requested was not found');
    $this->fixture->assertTextNotPresent('I found it');
  }

  #[@test]
  public function elements() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), trim('
      <html>
        <head>
          <title>Elements</title>
        </head>
        <body>
          <div id="header"/>
          <!-- <div id="navigation"/> -->
          <div id="main"/>
        </body>
      </html>
    '));

    $this->fixture->beginAt('/');
    $this->fixture->assertElementPresent('header');
    $this->fixture->assertElementNotPresent('footer');
    $this->fixture->assertElementPresent('main');
    $this->fixture->assertElementNotPresent('footer');
  }

  #[@test]
  public function images() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), trim('
      <html>
        <head>
          <title>Images</title>
        </head>
        <body>
          <img src="/static/blank.gif"/>
          <!-- <img src="http://example.com/example.png"/> -->
          <img src="http://example.com/logo.jpg"/>
        </body>
      </html>
    '));

    $this->fixture->beginAt('/');
    $this->fixture->assertImagePresent('/static/blank.gif');
    $this->fixture->assertImageNotPresent('http://example.com/example.png');
    $this->fixture->assertImageNotPresent('logo.jpg');
    $this->fixture->assertImagePresent('http://example.com/logo.jpg');
  }

  #[@test]
  public function links() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), trim('
      <html>
        <head>
          <title>Links</title>
        </head>
        <body>
          <a href="http://example.com/test">Test</a>
          <a href="/does-not-exist">404</a>
          <!-- <a href="comment.html">Hidden</a> -->
        </body>
      </html>
    '));

    $this->fixture->beginAt('/');
    $this->fixture->assertLinkPresent('http://example.com/test');
    $this->fixture->assertLinkPresent('/does-not-exist');
    $this->fixture->assertLinkNotPresent('comment.html');
    $this->fixture->assertLinkNotPresent('index.html');
  }

  #[@test]
  public function linksWithText() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), trim('
      <html>
        <head>
          <title>Links</title>
        </head>
        <body>
          <a href="http://example.com/test">Test</a>
          <a href="/does-not-exist">404</a>
          <!-- <a href="comment.html">Hidden</a> -->
        </body>
      </html>
    '));

    $this->fixture->beginAt('/');
    $this->fixture->assertLinkPresentWithText('Test');
    $this->fixture->assertLinkPresentWithText('404');
    $this->fixture->assertLinkNotPresentWithText('Hidden');
    $this->fixture->assertLinkNotPresentWithText('Hello');
  }

  #[@test]
  public function unnamedForm() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), trim('
      <html>
        <head>
          <title>Enter your name</title>
        </head>
        <body>
          <form action="http://example.com/"/>
        </body>
      </html>
    '));

    $this->fixture->beginAt('/');
    $this->fixture->assertFormPresent();
  }

  #[@test]
  public function noForm() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), trim('
      <html>
        <head>
          <title>Enter your name</title>
        </head>
        <body>
          <!-- TODO: Add form -->
        </body>
      </html>
    '));

    $this->fixture->beginAt('/');
    $this->fixture->assertFormNotPresent();
  }

  #[@test]
  public function namedForms() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), trim('
      <html>
        <head>
          <title>Blue or red pill?</title>
        </head>
        <body>
          <form name="blue" action="http://example.com/one"/>
          <form name="red" action="http://example.com/two"/>
        </body>
      </html>
    '));

    $this->fixture->beginAt('/');
    $this->fixture->assertFormPresent('red');
    $this->fixture->assertFormPresent('blue');
    $this->fixture->assertFormNotPresent('green');
  }
  
  #[@test]
  public function getForm() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), trim('
      <html>
        <head>
          <title>Form-Mania!</title>
        </head>
        <body>
          <form name="one" action="http://example.com/one"></form>
          <form name="two" method="POST" action="http://example.com/two"></form>
          <form name="three"></form>
        </body>
      </html>
    '));

    $this->fixture->beginAt('/');

    $this->assertForm('http://example.com/one', \peer\http\HttpConstants::GET, $this->fixture->getForm('one'));
    $this->assertForm('http://example.com/two', \peer\http\HttpConstants::POST, $this->fixture->getForm('two'));
    $this->assertForm('/', \peer\http\HttpConstants::GET, $this->fixture->getForm('three'));
  }

  #[@test, @expect('lang.IllegalArgumentException')]
  public function nonExistantField() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), $this->formFixture());
    $this->fixture->beginAt('/');

    $this->fixture->getForm()->getField('does-not-exist');
  }

  #[@test]
  public function textFieldWithoutValue() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), $this->formFixture());
    $this->fixture->beginAt('/');

    with ($f= $this->fixture->getForm()->getField('first')); {
      $this->assertClass($f, 'unittest.web.InputField');
      $this->assertEquals('first', $f->getName());
      $this->assertEquals(null, $f->getValue());
    }
  }

  #[@test]
  public function textFieldWithEmptyValue() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), $this->formFixture());
    $this->fixture->beginAt('/');

    with ($f= $this->fixture->getForm()->getField('initial')); {
      $this->assertClass($f, 'unittest.web.InputField');
      $this->assertEquals('initial', $f->getName());
      $this->assertEquals('', $f->getValue());
    }
  }

  #[@test]
  public function textFieldWithValue() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), $this->formFixture());
    $this->fixture->beginAt('/');

    with ($f= $this->fixture->getForm()->getField('last')); {
      $this->assertClass($f, 'unittest.web.InputField');
      $this->assertEquals('last', $f->getName());
      $this->assertEquals('Tester', $f->getValue());
    }
  }

  #[@test]
  public function textFieldWithUmlautInValue() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), $this->formFixture());
    $this->fixture->beginAt('/');

    with ($f= $this->fixture->getForm()->getField('uber')); {
      $this->assertClass($f, 'unittest.web.InputField');
      $this->assertEquals('uber', $f->getName());
      $this->assertEquals('�bercoder', $f->getValue());
    }
  }

  #[@test]
  public function selectFieldWithoutSelected() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), $this->formFixture());
    $this->fixture->beginAt('/');

    with ($f= $this->fixture->getForm()->getField('gender')); {
      $this->assertClass($f, 'unittest.web.SelectField');
      $this->assertEquals('gender', $f->getName());
      $this->assertEquals('-', $f->getValue());
    }
  }

  #[@test]
  public function selectFieldWithSelected() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), $this->formFixture());
    $this->fixture->beginAt('/');

    with ($f= $this->fixture->getForm()->getField('payment')); {
      $this->assertClass($f, 'unittest.web.SelectField');
      $this->assertEquals('payment', $f->getName());
      $this->assertEquals('C', $f->getValue());
    }
  }

  #[@test]
  public function selectFieldOptions() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), $this->formFixture());
    $this->fixture->beginAt('/');

    with ($options= $this->fixture->getForm()->getField('gender')->getOptions()); {
      $this->assertEquals(4, sizeof($options));

      $this->assertEquals('-', $options[0]->getValue());
      $this->assertEquals('(select one)', $options[0]->getText());
      $this->assertFalse($options[0]->isSelected());

      $this->assertEquals('M', $options[1]->getValue());
      $this->assertEquals('male', $options[1]->getText());
      $this->assertFalse($options[1]->isSelected());

      $this->assertEquals('F', $options[2]->getValue());
      $this->assertEquals('female', $options[2]->getText());
      $this->assertFalse($options[2]->isSelected());

      $this->assertEquals('U', $options[3]->getValue());
      $this->assertEquals('�berwoman', $options[3]->getText());
      $this->assertFalse($options[3]->isSelected());
    }
  }

  #[@test]
  public function selectFieldNoSelectedOptions() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), $this->formFixture());
    $this->fixture->beginAt('/');

    $this->assertEquals(array(), $this->fixture->getForm()->getField('gender')->getSelectedOptions());
  }
  
  #[@test]
  public function selectFieldSelectedOptions() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), $this->formFixture());
    $this->fixture->beginAt('/');

    with ($options= $this->fixture->getForm()->getField('payment')->getSelectedOptions()); {
      $this->assertEquals(1, sizeof($options));

      $this->assertEquals('C', $options[0]->getValue());
      $this->assertEquals('Cheque', $options[0]->getText());
      $this->assertTrue($options[0]->isSelected());
    }
  }

  #[@test]
  public function textArea() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), $this->formFixture());
    $this->fixture->beginAt('/');

    with ($f= $this->fixture->getForm()->getField('comments')); {
      $this->assertClass($f, 'unittest.web.TextAreaField');
      $this->assertEquals('comments', $f->getName());
      $this->assertEquals('(Comments)', $f->getValue());
    }

  }
  #[@test]
  public function textAreaWithUmlautInValue() {
    $this->fixture->respondWith(\peer\http\HttpConstants::STATUS_OK, array(), $this->formFixture());
    $this->fixture->beginAt('/');

    with ($f= $this->fixture->getForm()->getField('umlauts')); {
      $this->assertClass($f, 'unittest.web.TextAreaField');
      $this->assertEquals('umlauts', $f->getName());
      $this->assertEquals('�bercoder', $f->getValue());
    }
  }
}
