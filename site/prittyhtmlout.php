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
     * Constructor
     *
     * @param object $subject The object to observe
     * @param array $config  An array that holds the plugin configuration
     */
    private $formatHTML = false;
    private $formatScript = false;
    private $formatStyle = false;
    private $compress = false;
    private $indent_type = " ";
    private $indent_size = 2;
    private $config = null;
    private $onAdmin = false;
    private $onSite = false;

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
     * Log events.
     *
     * @param string $status The event to be logged.
     * @param string $comment A comment about the event.
     */
    private function _log ($status, $comment)
    {
        jimport('joomla.error.log');

        JLog::getInstance('plugin_system_prittyhtmlout_log.php')
        ->addEntry(array('status' => $status, 'comment' => $comment));
    }//function

    // This is a function for Joomla scripts to clean up HTML code before outputting it.
    // The function applies correct indentation to HTML/XHTML 1.0 and JavaScript
    // And makes the output much more readable.
    // You can specify the wanted indentation Type, indentation Size, format html, style, javascript
    // through the variable $indent, $formatHTML, $formatStyle and $formatScript
    
    //Function to seperate multiple tags one line
    /**
     * Clean html.
     *
     * @param string $fixthistext The text to be cleaned.
     */
    protected function fix_for_format_html($fixthistext){

	    // include javascript beautifier found on http://jsbeautifier.org/
	    require('jsbeautifier.php');

	    // First we compress the output html
	    // Creat the pattern and replacement array's

	    // Clean all scaces, tabs, newlines, carrige retuns, vertical tabs
	    // 1.) Match all carrige-retuns, newlines, tabs, vertical tabs
	    $pattern[] = "/(?<!:)\/\/(.*?)(?<!>)\n/";
	    // 1.) Strip all carrige-retuns, newlines, tabs, vertical tabs
	    $replacement[] = "/* $1 */";
	    // 2.) Match all spaces between closing and opening tags
	    $pattern[] = "/[\r\n\t]+/u";
	    // 1.) Strip all carrige-retuns, newlines, tabs, vertical tabs
	    $replacement[] = " ";
	    // 2.) Match all spaces between closing and opening tags
	    $pattern[] = "/>[ ]+</m";
	    // 2.) Strip all spaces between closing and opening tags
	    $replacement[] = "><";
	    // 3.) Match redundant spaces after tag name
	    $pattern[] = "/(<[\w]+)[ ]+/m";
	    // 3.) Strip redundant spaces after tag name
	    $replacement[] = "$1 ";
	    // 4.) Match all redundant spaces and set them to one space
	    $pattern[] = "/[ ]{2}/m";
	    // 4.) Strip all redundant spaces and set them to one space
	    $replacement[] = " ";
	    // 5.) Match missed spaces between tag attributes 
	    $pattern[] = "/([\w]+=\".+\")([\w])/m";
	    // 5.) Add  missedspaces between tag attributes
	    $replacement[] = "$1 $2";
	    // 6.) Match spaces before end of tags
	    $pattern[] = "/[ ]+>/m";
	    // 6) remove spaces before end of tag
	    $replacement[] = ">";
	    // 6.) Match spaces behind end of tags
	    $pattern[] = "/>[ ]+/m";
	    // 6) remove spaces behind end of tag
	    $replacement[] = ">";
	    // 7.) Match spaces before ending tags
	    $pattern[] = "/[ ]+<\//m";
	    // 7.) remove spaces before endending tags
	    $replacement[] = "</";
	    // 7.) Match spaces before begin tags
	    $pattern[] = "/[ ]+</m";
	    // 7.) remove spaces before begin tags
	    $replacement[] = "<";
	    // 7.) Match spaces before {
	    $pattern[] = "/[ ]*\{[ ]*/m";
	    // 7.) remove spaces before {
	    $replacement[] = "{";
	    // 7.) Match spaces before }
	    $pattern[] = "/[ ]*\}[ ]*/m";
	    // 7.) remove spaces before }
	    $replacement[] = "}";
	    // 7.) Match spaces and after ;
	    $pattern[] = "/;[ ]*/m";
	    // 7.) remove spaces after ;
	    $replacement[] = ";";
	    // 7.) Match spaces and after ;
	    $pattern[] = "/,[ ]*/m";
	    // 7.) remove spaces after ;
	    $replacement[] = ",";
	    //buffer for preg errors
	    $bufffixthistext = $fixthistext;
	    $fixthistext = preg_replace($pattern,$replacement,$fixthistext);
	    if($fixthistext==null){
		$fixthistext = $this->log_preg_error($bufffixthistext);
	    }
	    //end compressing

	    if($this->formatStyle){
		    // matching style tags
		    $bufffixthistext = $fixthistext;
		    $seach = "/(<style type=\"text\/css\">)(.*?)(<\/style>)/m";
		    $fixthistext = preg_replace_callback($seach,array($this,'formatStyleTag'),$fixthistext);
	    }
	    if($this->formatScript){
		    // matching script tags
		    $bufffixthistext = $fixthistext;
		    $seach = "/(<script type=\"text\/javascript\">|'text\/javascript'>)(.+?[\/]*[<]*)(<\/script>)/m";
		    $fixthistext = preg_replace_callback($seach,array($this,'formatScriptTag'),$fixthistext);
		    if($fixthistext==null){
			$fixthistext = $this->log_preg_error($bufffixthistext);
		    }
	    }
	    if($this->formatHTML){
		    // matching html tags
		    $bufffixthistext = $fixthistext;
		    $match = "/(?<!<!--|'|\")(<(\/)?(\w+)((\s+\w+(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)(\/)?>)(?(2)|(<\/\\3>)?)/m";
		    $fixthistext = preg_replace_callback($match,array($this,'formatHTMLTags'),$fixthistext);
		    if($fixthistext==null){
			$fixthistext = $this->log_preg_error($bufffixthistext);
		    }
	    }
	    // build array 
	    $fixthistext_array = explode("\n", $fixthistext);
	    foreach ($fixthistext_array as $unfixedtextkey => $unfixedtextvalue)
	    {
		    //Makes sure empty lines are ignores
		    if (!preg_match("/^(\s)*$/", $unfixedtextvalue))
		    {
			    //Clean spaces at start and end of line
			    $fixedtextvalue = $unfixedtextvalue;
			    $fixedtext_array[$unfixedtextkey] = $fixedtextvalue;
		    }
	    }
	    //var_dump($fixedtext_array);
	    return implode("\n", $fixedtext_array);
    }//end function

    /**
     * Format script tags.
     *
     * @param array $matches The array of script-tags to be formated.
     * @return string Formated script-tags.
     */
    protected function formatScriptTag($matches){
	
	$script = preg_replace("/((<\!--)|(-->))/", "\n$1\n", $matches[2]);
	$opts = new BeautifierOptions();
;
	$opts->indent_size = $this->indent_size;
	if($this->indent_type == "\t"){
	    $opts->indent_with_tabs = true;
	}
	$opts->keep_array_indentation = true;
	$opts->keep_function_indentation = true;
	$opts->jslint_happy = true;
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
	$pattern[] = "/([\w-]+:.*?;)/";
	$replace[] = "\n".str_repeat($this->indent_type, $this->indent_size)."$1";
	$style = preg_replace($pattern,$replace,$matches[2]);
	return ($this->formatHTML) ? $matches[1].$style.$matches[3] : "\n".$matches[1]."\n".$style."\n".$matches[3]."\n";
    }//end function

    /**
     * Format html tags.
     *
     * @param array $matches The array of html-tags to be formated.
     * @return string Formated html-tags.
     */
    protected function formatHTMLTags($matches){
	// repair broken selfclosing tags
	$match = preg_replace("/<(link|meta|base|br|img|hr|col|input)(.*?)(?<!\/)>/","<$1$2/>",$matches[0]);
	// set each tag on new line
	return "\n".$match."\n";
    }//end function

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
	    $indent = str_repeat($this->indent_type,$this->indent_size);
	    $htmlTags = "|a|abbr|acronym|address|applet|area|b|base|basefont|bdo|big|blockquote|body|br|button|caption|center|cite|code|col|colgroup|dd|del|dfn|dir|div|dl|dt|em|fieldset|font|form|frame|frameset|h1|h2|h3|h4|h5|h6]|head|hr|html|i|iframe|img|input|ins|isindex|kbd|label|legend|li|link|map|menu|meta|noframes|noscript|object|ol|optgroup|option|p|param|pre|q|s|samp|script|select|small|span|strike|strong|style|sub|sup|table|tbody|td|textarea|tfoot|th|thead|title|tr|tt|u|ul|var";

	    //Uses previous function to seperate tags
	    $fixed_uncleanhtml = $this->fix_for_format_html($uncleanhtml);
	    //return $fixed_uncleanhtml;
	    $uncleanhtml_array = explode("\n", $fixed_uncleanhtml);
	    //Sets no indentation
	    $indentlevel = 0;
	    //return implode("\n", $uncleanhtml_array);
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
		    else if (preg_match("/<[^\/](".$htmlTags.")(.*?)>/", $currentuncleanhtml) && preg_match("/<\/(".$htmlTags.")>/", $currentuncleanhtml))
		    { 
			    $cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
		    }
		    //If closing HTML tag or closing JavaScript clams, decrease indentation and then apply the new level
		    //else if (preg_match("/<\/(.*)>/", $currentuncleanhtml) || preg_match("/^(\s|\t)*\}{1}(\s|\t)*$/", $currentuncleanhtml))
		    else if (preg_match("/<\/(".$htmlTags.")>/", $currentuncleanhtml))//||preg_match("/(?<!\{)\}/", $currentuncleanhtml))
		    {
			    //if(!preg_match("/[\}]/", $currentuncleanhtml)){
			      $indentlevel--;
			      $found = true;
			    //}
			    $replaceindent = "";
			    for ($o = 0; $o < $indentlevel; $o++)
			    {
				    $replaceindent .= $indent;
			    }
			    
			    $cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
		    }
		    //If opening HTML tag AND not a stand-alone tag, or opening JavaScript clams, increase indentation and then apply new level
		    //else if ((preg_match("/<[^\/](.*)>/", $currentuncleanhtml) && !preg_match("/<(link|meta|base|br|img|hr)(.*)>/", $currentuncleanhtml)) || preg_match("/^(\s|\t)*\{{1}(\s|\t)*$/", $currentuncleanhtml))
		    else if ((preg_match("/(?<!<!--)<[^\/!?](".$htmlTags.")(.*)>/", $currentuncleanhtml) && !preg_match("/<(link|meta|base|br|img|hr|col|input)(.*)>/", $currentuncleanhtml)))//||preg_match("/\{(?!\})/", $currentuncleanhtml))
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
	    //return var_dump($cleanhtml_array);//implode("\n", $cleanhtml_array);	
	    return implode("\n", $cleanhtml_array);	
    }//end function

    protected function addScript($doc){
	$js_code = "<script type='text/javascript'>
	    window.addEvent('domready', function() {
	    if($('jform_params_compress0').checked==true){
		$('jform_params_formatHTML').disabled=true;
		$('jform_params_formatStyle').disabled=true;
		$('jform_params_formatScript').disabled=true;
	    }
	    if($('jform_params_compress1').checked==true){
		$('jform_params_formatHTML').disabled=false;
		$('jform_params_formatStyle').disabled=false;
		$('jform_params_formatScript').disabled=false;
	    }
	    $('jform_params_compress').addEvent('click', function(){
		    if($('jform_params_compress0').checked==true){
			$('jform_params_formatHTML').disabled=true;
			$('jform_params_formatStyle').disabled=true;
			$('jform_params_formatScript').disabled=true;
		    }
		    if($('jform_params_compress1').checked==true){
			$('jform_params_formatHTML').disabled=false;
			$('jform_params_formatStyle').disabled=false;
			$('jform_params_formatScript').disabled=false;
		    }
		});
	    });
	</script>";
	$doc = preg_replace("/<\/body>\s*?<\/html>/",$js_code."\n</body>\n</html>",$doc);
	return $doc;
    }//end function

    protected function log_preg_error($text){
	switch(preg_last_error()){
	    case PREG_BACKTRACK_LIMIT_ERROR:
		$this->_log(
		    'fix_for_clean_html',
		    'PREG_BACKTRACK_LIMIT_ERROR'
		);
		return $text;
	    case PREG_RECURSION_LIMIT_ERROR:
		$this->_log(
		    'fix_for_clean_html',
		    'PREG_RECURSION_LIMIT_ERROR'
		);
		return $text;
	    case PREG_BAD_UTF8_ERROR:
		/*$this->_log(
		    'fix_for_clean_html',
		    'PREG_BAD_UTF8_ERROR: Fixed by utf8_encode' 
		);*/
		$fixthistext = utf8_encode($text);
		return utf8_encode($text);
	    case PREG_INTERNAL_ERROR:
		$this->_log(
		    'fix_for_clean_html',
		    'PREG_INTERNAL_ERROR'
		);
		return $text;
	}
    }//end function
}//class