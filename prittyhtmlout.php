<?php
/**
 * @version 	SVN: $Id: builder.php 469 2011-07-29 19:03:30Z elkuku $
 * @package    	prittyHTMLOut
 * @subpackage 	Base
 * @author     	Klaus Wilms {@link http://www.unitsystems.de}
 * @author     	Created on 14-Nov-2011
 * @license    	GNU/GPL
 */

//-- No direct access
defined('_JEXEC') || die('=;)');

jimport('joomla.plugin.plugin');

/**
 * System Plugin.
 *
 * @package    prittyHTMLOut
 * @subpackage Plugin
 */
class plgSystemprittyHTMLOut extends JPlugin
{
    /**
    * Indent type tab.
    * @var    string
    * @since  1.0.0
    */
    const PHO_TAB = "\t";

    /**
    * Indent type space.
    * @var    string
    * @since  1.0.0
    */
    const PHO_SPACE = " ";

    /**
    * Format Html.
    * @var    boolean
    * @since  1.0.0
    */
    private $formatHTML = false;

    /**
    * Format Script.
    * @var    boolean
    * @since  1.0.0
    */
    private $formatScript = false;

    /**
    * Format Style.
    * @var    boolean
    * @since  1.0.0
    */
    private $formatStyle = false;

    /**
    * The indent type.
    * @var    const
    * @since  1.0.0
    */
    private $indent_type = PHO_SPACE;

    /**
    * The indent size.
    * @var    integer
    * @since  1.0.0
    */
    private $indent_size = 2;

    /**
    * An array that holds the plugin configuration.
    * @var    object
    * @since  1.0.0
    */
    private $config = null;

    /**
    * Enable's the plugin on backend.
    * @var    boolean
    * @since  1.0.0
    */
    private $onAdmin = false;

    /**
    * Enable's the plugin on frontend.
    * @var    boolean
    * @since  1.0.0
    */
    private $onSite = false;

    /**
     * Constructor
     *
     * @param object $subject The object to observe
     * @param array $config  An array that holds the plugin configuration
     */
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->formatHTML = $this->params->get('formatHTML', true);
        $this->formatScript = $this->params->get('formatScript', true);
        $this->formatStyle = $this->params->get('formatStyle', true);
        $this->compress = $this->params->get('compress', false);
        $this->indent_size = $this->params->get('indent_size', 2);
        $this->indent_type = $this->params->get('indent_type', " ");
        $this->onAdmin = $this->params->get('onAdmin', false);
        $this->onSite = $this->params->get('onSite', false);
    }//function

    /**
     * prittyHTMLOut format html onAfterRender
     */
    public function onAfterRender()
    {
	$output = JResponse::getBody();
	$parms = $this->params;
	if($this->onAdmin || $this->onSite){
	    $html = $this->indent_html_code($output);
	    JResponse::setBody($html);
	}
        return true;
    }//function

    /**
     * Main function to clean and indent the html code.
     *
     * @param string $uncleanhtml The text to be cleaned and indented.
     */
    protected function indent_html_code($uncleanhtml){
	$app = &JFactory::getApplication();
	if($app->isAdmin() && !$this->onAdmin){
	    return $uncleanhtml;
	}
	if($app->isSite() && !$this->onSite){
	    return $uncleanhtml;
	}
	// Copy from DZone Snippets http://snippets.dzone.com/posts/show/1964

	//Set wanted indentation
	if($this->indent_type == " ")
	    $indent = str_repeat($this->indent_type,$this->indent_size);
	//Fixed tab error
	if($this->indent_type == "tab"){
	    for($i=0;$i<$this->indent_size;$i++){
		$indent .= "\t";
	    }
	}

	//The html tags
	$htmlTags = "|a|abbr|acronym|address|applet|area|b|base|basefont|bdo|big|blockquote|body|br|button|caption|center|cite|code|col|colgroup|dd|del|dfn|dir|div|dl|dt|em|fieldset|font|form|frame|frameset|h1|h2|h3|h4|h5|h6]|head|hr|html|i|iframe|img|input|ins|isindex|kbd|label|legend|li|link|map|menu|meta|noframes|noscript|object|ol|optgroup|option|p|param|pre|q|s|samp|script|select|small|span|strike|strong|style|sub|sup|table|tbody|td|textarea|tfoot|th|thead|title|tr|tt|u|ul|var";

	//Uses fix_for_format_html function to seperate tags
	$fixed_uncleanhtml = $this->fix_for_format_html($uncleanhtml);

	//return $fixed_uncleanhtml;
	$uncleanhtml_array = explode("\n", $fixed_uncleanhtml);

	//Sets no indentation
	$indentlevel = 0;

	//Indent the html code
	foreach ($uncleanhtml_array as $uncleanhtml_key => $currentuncleanhtml)
	{
	    trim($currentuncleanhtml);
	    $replaceindent = "";
	    
	    //Sets the indentation from current indentlevel
	    for ($o = 0; $o < $indentlevel; $o++)
	    {
		$replaceindent .= $indent;
	    }
	    
	    //If self-closing tag, simply apply indent
	    if (preg_match("/<.*?(\"|'.*?[^>]'|\")[^\/]?\/>/", $currentuncleanhtml))
	    { 
		    $cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
	    }
	    //If doctype declaration, simply apply indent
	    else if (preg_match("/<!DOCTYPE(.*)>/i", $currentuncleanhtml))
	    { 
		    $cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
	    }
	    //If opening AND closing tag on same line, simply apply indent
	    else if (preg_match("/<[^\/](".$htmlTags.")(.*?)>/", $currentuncleanhtml) && preg_match("/<\/(".$htmlTags.")>/" , $currentuncleanhtml) || preg_match("/(?<='|\")</" , $currentuncleanhtml))
	    { 
		    $cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
	    }
	    //If closing HTML tag or closing JavaScript clams, decrease indentation and then apply the new level
	    else if (preg_match("/<\/(".$htmlTags.")>/", $currentuncleanhtml))
	    {
		    $indentlevel--;
		    $found = true;
		    $replaceindent = "";
		    for ($o = 0; $o < $indentlevel; $o++)
		    {
			    $replaceindent .= $indent;
		    }
		    
		    $cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
	    }
	    //If opening HTML tag AND not a stand-alone tag, or opening JavaScript clams, increase indentation and then apply new level
	    else if ((preg_match("/(?<!<!--|'|\")<[^\/!?](".$htmlTags.")(.*)>/", $currentuncleanhtml) && !preg_match("/<(link|meta|base|br|img|hr|col|input)(.*)>/", $currentuncleanhtml) && !preg_match("/.*?;$/", $currentuncleanhtml)))
	    {
		    $cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
		    $indentlevel++;
		    $replaceindent = "";
		    for ($o = 0; $o < $indentlevel; $o++)
		    {
			    $replaceindent .= $indent;
		    }
	    }
	    else
	    //Else, only apply indentation
	    {$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;}
	}

	//Return single string seperated by newline
	return implode("\n", $cleanhtml_array);	
    }//end function

    // This is a function for Joomla scripts to clean up HTML code before outputting it.
    // The function applies correct indentation to HTML/XHTML 1.0 and JavaScript
    // And makes the output much more readable.
    // You can specify the wanted indentation Type, indentation Size, format html, style, javascript
    // through the variable $indent, $formatHTML, $formatStyle and $formatScript
    
    /**
     * Clean html.
     *
     * @param string $fixthistext The text to be cleaned.
     */
    protected function fix_for_format_html($fixthistext){

	// include javascript beautifier found on http://jsbeautifier.org/
	require('jsbeautifier.php');

	//fix for utf-8 preg_replace errors
	mb_detect_encoding($fixthistext, "UTF-8") == "UTF-8" ? $fixthistext =  $fixthistext : $fixthistext = utf8_encode($fixthistext);

	//fix for html in title tags
	$match = "/(title=\")(.*?)(\")/";
	$fixthistext = preg_replace_callback($match,array($this,'fix_title_tags'),$fixthistext);
		
	// First we compress the output html,
	// creat the pattern and replacement array's
	// and clean all scaces, tabs, newlines, carrige retuns, vertical tabs

	// 1.) Match all carrige-retuns, newlines, tabs, vertical tabs
	$pattern[] = "/(?<!:)\/\/(.*?)(?<!>)\n/";
	// 1.) Strip all carrige-retuns, newlines, tabs, vertical tabs
	$replacement[] = "/* $1 */";

	// 2.) Match all spaces between closing and opening tags
	$pattern[] = "/[\r\n\t]+/u";
	// 2.) Strip all carrige-retuns, newlines, tabs, vertical tabs
	$replacement[] = " ";

	// 3.) Match all spaces between closing and opening tags
	$pattern[] = "/>[ ]+</m";
	// 3.) Strip all spaces between closing and opening tags
	$replacement[] = "><";

	// 4.) Match redundant spaces after tag name
	$pattern[] = "/(<[\w]+)[ ]+/m";
	// 4.) Strip redundant spaces after tag name
	$replacement[] = "$1 ";

	// 5.) Match all redundant spaces and set them to one space
	$pattern[] = "/[ ]{2}/m";
	// 5.) Strip all redundant spaces and set them to one space
	$replacement[] = " ";

	// 6.) Match missed spaces between tag attributes 
	$pattern[] = "/([\w]+=\".+\")([\w])/m";
	// 6.) Add  missedspaces between tag attributes
	$replacement[] = "$1 $2";

	// 7.) Match spaces before end of tags
	$pattern[] = "/[ ]+>/m";
	// 7) remove spaces before end of tag
	$replacement[] = ">";

	// 8.) Match spaces behind end of tags
	$pattern[] = "/>[ ]+/m";
	// 8) remove spaces behind end of tag
	$replacement[] = ">";

	// 9.) Match spaces before ending tags
	$pattern[] = "/[ ]+<\//m";
	// 9.) remove spaces before endending tags
	$replacement[] = "</";

	// 10.) Match spaces before begin tags
	$pattern[] = "/[ ]+</m";
	// 10.) remove spaces before begin tags
	$replacement[] = "<";

	// 11.) Match spaces before {
	$pattern[] = "/[ ]*\{[ ]*/m";
	// 11.) remove spaces before {
	$replacement[] = "{";

	// 12.) Match spaces before }
	$pattern[] = "/[ ]*\}[ ]*/m";
	// 12.) remove spaces before }
	$replacement[] = "}";

	// 13.) Match spaces and after ;
	$pattern[] = "/;[ ]*/m";
	// 13.) remove spaces after ;
	$replacement[] = ";";

	// 14.) Match spaces and after ;
	$pattern[] = "/,[ ]*/m";
	// 14.) remove spaces after ;
	$replacement[] = ",";

	// 15.) Match spaces after begin tags
	//$pattern[] = "/<[ ]+/m";
	// 15.) remove spaces after begin tags
	//$replacement[] = "<";

	//compress the output
	$fixthistext = preg_replace($pattern,$replacement,$fixthistext);
	//end compressing

	//Format the style tag's
	if($this->formatStyle){
		//buffer for preg_replace_callback errors
		$bufffixthistext = $fixthistext;
		// matching style tags
		$seach = "/(<style type=\"text\/css\">)(.*?)(<\/style>)/m";
		$fixthistext = preg_replace_callback($seach,array($this,'formatStyleTag'),$fixthistext);
		//check for preg_replace_callback errors
		if($fixthistext==null){
		    $fixthistext = $bufffixthistext;
		}
	}

	//Format the script tag's
	if($this->formatScript){
		//buffer for preg_replace_callback errors
		$bufffixthistext = $fixthistext;
		// matching script tags
		$seach = "/(<script type=\"text\/javascript\">|'text\/javascript'>)(.+?[\/]*[<]*)(<\/script>)/m";
		$fixthistext = preg_replace_callback($seach,array($this,'formatScriptTag'),$fixthistext);
		//check for preg_replace_callback errors
		if($fixthistext==null){
		    $fixthistext = "<b>Fehler</b>\n".$bufffixthistext;
		}
	}

	//Format the html tag's
	if($this->formatHTML){
		//buffer for preg_replace_callback errors
		$bufffixthistext = $fixthistext;
		// matching html tags
		$match = "/(?<!<!--|'|\")(<(\/)?(\w+)((\s+\w+(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)(\/)?>)(?(2)|(<\/\\3>)?)/m";
		$fixthistext = preg_replace_callback($match,array($this,'formatHTMLTags'),$fixthistext);
		//check for preg_replace_callback errors
		if($fixthistext==null){
		    $fixthistext = $bufffixthistext;
		}
	}

	// build array 
	$fixthistext_array = explode("\n", $fixthistext);
    
	//Makes sure empty lines are ignores
	foreach ($fixthistext_array as $unfixedtextkey => $unfixedtextvalue)
	{		    
		if (!preg_match("/^(\s)*$/", $unfixedtextvalue))
		{
			$fixedtextvalue = $unfixedtextvalue;
			$fixedtext_array[$unfixedtextkey] = $fixedtextvalue;
		}
	}
	return implode("\n", $fixedtext_array);
    }//end function

    /**
     * Format script tags.
     *
     * @param array $matches The array of script-tags to be formated.
     * @return string Formated script-tags.
     */
    protected function formatScriptTag($matches){
	//buffer for preg_replace errors
	$buffScript = $matches[2];
	$script = preg_replace("/((<\!--)|(-->))/", "\n$1\n", $matches[2]);
	//check for preg_replace_callback errors
	if($script==null){
	    $script = $buffScript;
	}
	
	//create a new BeautifierOptions object and set it's value's
	$opts = new BeautifierOptions();
	$opts->indent_size = $this->indent_size;
	if($this->indent_type == "\t"){
	    $opts->indent_with_tabs = true;
	}
	$opts->keep_array_indentation = true;
	$opts->keep_function_indentation = true;
	$opts->jslint_happy = true;

	//beautify the script tags
	$res = js_beautify($script, $opts);
	return ($this->formatHTML) ? $matches[1].$res."\n".$matches[3] : "\n".$matches[1]."\n".$res."\n".$matches[3]."\n";
    }//end function

    /**
     * Format style tags.
     *
     * @param array $matches The array of style-tags to be formated.
     * @return string Formated style-tags.
     */
    protected function formatStyleTag($matches){
	$pattern[] = "/\{/";
	$replace[] = "{\n";
	$pattern[] = "/\}/";
	$replace[] = "\n}\n";
	$pattern[] = "/(\/*(.*?)*\/)/";
	$replace[] = "\n$1\n";
	$pattern[] = "/([\w-]+:.*?;)/";
	$replace[] = "\n".str_repeat($this->indent_type, $this->indent_size)."$1";
	//buffer for preg_replace errors
	$buffStyle = $matches[2];
	$style = preg_replace($pattern,$replace,$matches[2]);
	//check for preg_replace_callback errors
	if($style==null){
	    $style = $buffStyle;
	}
	return ($this->formatHTML) ? $matches[1].$style.$matches[3] : "\n".$matches[1]."\n".$style."\n".$matches[3]."\n";
    }//end function

    /**
     * Format html tags.
     *
     * @param array $matches The array of html-tags to be formated.
     * @return string Formated html-tags.
     */
    protected function formatHTMLTags($matches){
	//buffer for preg_replace errors
	$buffMatch = $matches[0];
	// repair broken selfclosing tags
	$match = preg_replace("/<(link|meta|base|br|img|hr|col|input)(.*?)(?<!\/)>/","<$1$2/>",$matches[0]);
	//check for preg_replace_callback errors
	if($match==null){
	    $match = $buffMatch;
	}
	// set each tag on new line
	return "\n".$match."\n";
    }//end function
    
    /**
     * Fix for html tags in title attributes.
     *
     * @param array $matches The array of html-tags to be formated.
     * @return string Fixed titel attribute.
     */
    protected function fix_title_tags($matches){
    	return $matches[1].htmlentities($matches[2],ENT_QUOTES,"UTF-8").$matches[3];
    }
}//class