<?php
/* This class is part of the XP framework
 *
 * $Id$
 */

  uses('scriptlet.xml.XMLScriptletRequest');
  
  /**
   * Wraps request
   *
   * @see      xp://scriptlet.xml.XMLScriptletRequest
   * @purpose  Scriptlet request wrapper
   */
  class WorkflowScriptletRequest extends XMLScriptletRequest {
    var
      $classloader  = NULL,
      $state        = NULL;

    /**
     * Constructor
     *
     * @access  public
     * @param   &lang.ClassLoader classloader
     */
    function __construct(&$classloader) {
      $this->classloader= &$classloader;
    }

    /**
     * Initialize this request object - overridden from base class.
     *
     * @access  public
     * @see     xp://scriptlet.xml.XMLScriptletRequest#initialize
     */
    function initialize() {
      parent::initialize();
      if ($this->stateName) {
        $name= implode('', array_map('ucfirst', array_reverse(explode('/', $this->stateName))));
        try(); {
          $class= &$this->classloader->loadClass('state.'.$name.'State');
        } if (catch('ClassNotFoundException', $e)) {
          $this->state= &xp::null();
          return throw($e);
        }

        $this->state= &$class->newInstance();
      }
    }
  }
?>
