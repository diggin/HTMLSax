<?php

/**
* Base State Parser
* @package XML_HTMLSax3
* @access protected
* @abstract
*/
class XML_HTMLSax3_StateParser {
    /**
    * Instance of user front end class to be passed to callbacks
    * @var XML_HTMLSax3
    * @access private
    */
    var $htmlsax;
    /**
    * User defined object for handling elements
    * @var object
    * @access private
    */
    var $handler_object_element;
    /**
    * User defined open tag handler method
    * @var string
    * @access private
    */
    var $handler_method_opening;
    /**
    * User defined close tag handler method
    * @var string
    * @access private
    */
    var $handler_method_closing;
    /**
    * User defined object for handling data in elements
    * @var object
    * @access private
    */
    var $handler_object_data;
    /**
    * User defined data handler method
    * @var string
    * @access private
    */
    var $handler_method_data;
    /**
    * User defined object for handling processing instructions
    * @var object
    * @access private
    */
    var $handler_object_pi;
    /**
    * User defined processing instruction handler method
    * @var string
    * @access private
    */
    var $handler_method_pi;
    /**
    * User defined object for handling JSP/ASP tags
    * @var object
    * @access private
    */
    var $handler_object_jasp;
    /**
    * User defined JSP/ASP handler method
    * @var string
    * @access private
    */
    var $handler_method_jasp;
    /**
    * User defined object for handling XML escapes
    * @var object
    * @access private
    */
    var $handler_object_escape;
    /**
    * User defined XML escape handler method
    * @var string
    * @access private
    */
    var $handler_method_escape;
    /**
    * User defined handler object or NullHandler
    * @var object
    * @access private
    */
    var $handler_default;
    /**
    * Parser options determining parsing behavior
    * @var array
    * @access private
    */
    var $parser_options = array();
    /**
    * XML document being parsed
    * @var string
    * @access private
    */
    var $rawtext;
    /**
    * Position in XML document relative to start (0)
    * @var int
    * @access private
    */
    var $position;
    /**
    * Length of the XML document in characters
    * @var int
    * @access private
    */
    var $length;
    /**
    * Array of state objects
    * @var array
    * @access private
    */
    var $State = array();

    /**
    * Constructs XML_HTMLSax3_StateParser setting up states
    * @var XML_HTMLSax3 instance of user front end class
    * @access protected
    */
    function __construct ($htmlsax) {
        $this->htmlsax = $htmlsax;
        $this->State[XML_HTMLSAX3_STATE_START] = new XML_HTMLSax3_StartingState();

        $this->State[XML_HTMLSAX3_STATE_CLOSING_TAG] = new XML_HTMLSax3_ClosingTagState();
        $this->State[XML_HTMLSAX3_STATE_TAG] = new XML_HTMLSax3_TagState();
        $this->State[XML_HTMLSAX3_STATE_OPENING_TAG] = new XML_HTMLSax3_OpeningTagState();

        $this->State[XML_HTMLSAX3_STATE_PI] = new XML_HTMLSax3_PiState();
        $this->State[XML_HTMLSAX3_STATE_JASP] = new XML_HTMLSax3_JaspState();
        $this->State[XML_HTMLSAX3_STATE_ESCAPE] = new XML_HTMLSax3_EscapeState();
    }

    /**
    * Moves the position back one character
    * @access protected
    * @return void
    */
    function unscanCharacter() {
        $this->position -= 1;
    }

    /**
    * Moves the position forward one character
    * @access protected
    * @return void
    */
    function ignoreCharacter() {
        $this->position += 1;
    }

    /**
    * Returns the next character from the XML document or void if at end
    * @access protected
    * @return mixed
    */
    function scanCharacter() {
        if ($this->position < $this->length) {
            return $this->rawtext{$this->position++};
        }
    }

    /**
    * Returns a string from the current position to the next occurance
    * of the supplied string
    * @param string string to search until
    * @access protected
    * @return string
    */
    function scanUntilString($string) {
        $start = $this->position;
        $this->position = strpos($this->rawtext, $string, $start);
        if ($this->position === FALSE) {
            $this->position = $this->length;
        }
        return substr($this->rawtext, $start, $this->position - $start);
    }

    /**
    * Returns a string from the current position until the first instance of
    * one of the characters in the supplied string argument
    * @param string string to search until
    * @access protected
    * @return string
    * @abstract
    */
    function scanUntilCharacters($string) {}

    /**
    * Moves the position forward past any whitespace characters
    * @access protected
    * @return void
    * @abstract
    */
    function ignoreWhitespace() {}

    /**
    * Begins the parsing operation, setting up any decorators, depending on
    * parse options invoking _parse() to execute parsing
    * @param string XML document to parse
    * @access protected
    * @return void
    */
    function parse($data) {
        if ($this->parser_options['XML_OPTION_TRIM_DATA_NODES']==1) {
            $decorator = new XML_HTMLSax3_Trim(
                $this->handler_object_data,
                $this->handler_method_data);
            $this->handler_object_data = $decorator;
            $this->handler_method_data = 'trimData';
        }
        if ($this->parser_options['XML_OPTION_CASE_FOLDING']==1) {
            $open_decor = new XML_HTMLSax3_CaseFolding(
                $this->handler_object_element,
                $this->handler_method_opening,
                $this->handler_method_closing);
            $this->handler_object_element = $open_decor;
            $this->handler_method_opening ='foldOpen';
            $this->handler_method_closing ='foldClose';
        }
        if ($this->parser_options['XML_OPTION_LINEFEED_BREAK']==1) {
            $decorator = new XML_HTMLSax3_Linefeed(
                $this->handler_object_data,
                $this->handler_method_data);
            $this->handler_object_data = $decorator;
            $this->handler_method_data = 'breakData';
        }
        if ($this->parser_options['XML_OPTION_TAB_BREAK']==1) {
            $decorator = new XML_HTMLSax3_Tab(
                $this->handler_object_data,
                $this->handler_method_data);
            $this->handler_object_data = $decorator;
            $this->handler_method_data = 'breakData';
        }
        if ($this->parser_options['XML_OPTION_ENTITIES_UNPARSED']==1) {
            $decorator = new XML_HTMLSax3_Entities_Unparsed(
                $this->handler_object_data,
                $this->handler_method_data);
            $this->handler_object_data = $decorator;
            $this->handler_method_data = 'breakData';
        }
        if ($this->parser_options['XML_OPTION_ENTITIES_PARSED']==1) {
            $decorator = new XML_HTMLSax3_Entities_Parsed(
                $this->handler_object_data,
                $this->handler_method_data);
            $this->handler_object_data = $decorator;
            $this->handler_method_data = 'breakData';
        }
        // Note switched on by default
        if ($this->parser_options['XML_OPTION_STRIP_ESCAPES']==1) {
            $decorator = new XML_HTMLSax3_Escape_Stripper(
                $this->handler_object_escape,
                $this->handler_method_escape);
            $this->handler_object_escape = $decorator;
            $this->handler_method_escape = 'strip';
        }
        $this->rawtext = $data;
        $this->length = strlen($data);
        $this->position = 0;
        $this->_parse();
    }

    /**
    * Performs the parsing itself, delegating calls to a specific parser
    * state
    * @param constant state object to parse with
    * @access protected
    * @return void
    */
    function _parse($state = XML_HTMLSAX3_STATE_START) {
        do {
            $state = $this->State[$state]->parse($this);
        } while ($state != XML_HTMLSAX3_STATE_STOP &&
                    $this->position < $this->length);
    }
}