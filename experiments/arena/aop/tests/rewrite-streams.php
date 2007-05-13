<?php
  require('lang.base.php');
  xp::sapi('cli');
  uses(
    'text.StreamTokenizer', 
    'io.streams.FileInputStream', 
    'io.File',
    'util.profiling.Timer'
  );
  
  class Aop extends Object {
    public static $pointcuts= array();
  }
  
  class IOWrapper extends Object {
    private $f, $class= NULL;
    
    // {{{ function bool stream_open(string path, string mode, int options, string opened_path)
    //     Open the given stream and check if file exists
    function stream_open($path, $mode, $options, $opened_path) {
      stream_wrapper_restore('file');
      $this->f= new StreamTokenizer(new FileInputStream(new File($path)), " \r\n\t", TRUE);
      return TRUE;
    }
    // }}}
    
    // {{{ string stream_read(int count)
    //     Read $count bytes up-to-length of file
    function stream_read($count) {
      $t= $this->f->nextToken();

      if ('class' === $t) {         // FIXME: Check strings or comments
        $ws= $this->f->nextToken();
        $this->class= $this->f->nextToken(' {');
        return $t.$ws.$this->class;
      }
      
      if ($this->class && 'function' === $t) {
        $ws= $this->f->nextToken();
        $name= $this->f->nextToken('(');
        $this->f->nextToken('(');
        // DEBUG fputs(STDERR, "NAME = # $this->class::$name #\n");
        if (!isset(Aop::$pointcuts[$this->class.'::'.$name])) {
          return $t.$ws.$name.'(';
        }
        
        $args= '('.$this->f->nextToken('{');
        $this->f->nextToken('{');
        $t= 'function '.$name.$args.'{ ';
        
        // @before
        $t.= 'call_user_func_array(Aop::$pointcuts[\''.$this->class.'::'.$name.'\'][\'before\'], array'.$args.');';
        
        // @except
        $t.= 'try { $r= $this->�'.$name.$args.'; } catch (Exception $e) { call_user_func(Aop::$pointcuts[\''.$this->class.'::'.$name.'\'][\'except\'], $e); throw $e; } ';
        
        // @after
        $t.= 'call_user_func(Aop::$pointcuts[\''.$this->class.'::'.$name.'\'][\'after\'], $r); return $r;';
        
        $t.= '} function �'.$name.$args.' {';
        
        // DEBUG fputs(STDERR, $t."\n");
      }

      return $t;
    }
    // }}}
    
    // {{{ bool stream_eof()
    //     Returns whether stream is at end of file
    function stream_eof() {
      $eof= !$this->f->hasMoreTokens();
      if ($eof) {
        stream_wrapper_unregister('file');
        stream_wrapper_register('file', __CLASS__);
      }
      return $eof;
    }
    // }}}
    
    // {{{ <string,int> url_stat(string path)
    //     Retrieve status of url
    function url_stat($path) {
      stream_wrapper_restore('file');
      $r= @stat($path);
      stream_wrapper_unregister('file');
      stream_wrapper_register('file', __CLASS__);
      return $r;
    }
    // }}}
  
  }
  
  #[@aspect]
  class PowerAspect extends Object {
  
    #[@pointcut]
    public function settingPower() {
      return 'Binford::setPoweredBy';
    }
  
    #[@before('settingPower')]
    public function checkPower($p) {
      if ($p != 6100 && $p != 611) { 
        throw new IllegalArgumentException('Power must either be 611 or 6100'); 
      }
    }

    #[@after('settingPower')]
    public function logPower() {
      Console::writeLine('Power successfully set!');
    }
  }
  
  // Install stream wrapper
  $p= new ParamString();
  if (!$p->exists('disable')) {
    stream_wrapper_unregister('file');
    stream_wrapper_register('file', 'IOWrapper');
  }
    
  // Register pointcuts
  $pa= new PowerAspect();
  Aop::$pointcuts['Binford::setPoweredBy']= array(
    'before' => array($pa, 'checkPower'),
    'after'  => array($pa, 'logPower'),
  );
  
  // Load binford class
  $t= new Timer();
  $t->start();
  XPClass::forName('util.Binford');
  $t->stop();
  
  // Create an instance
  $bf= create(new Binford($p->value(1, NULL, 6100)));
  
  Console::writeLinef('%s - took %.3f seconds', $bf->toString(), $t->elapsedTime());
?>
