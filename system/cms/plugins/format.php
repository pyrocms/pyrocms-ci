<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Format Plugin
 *
 * Various text formatting functions.
 *
 * @author		PyroCMS Dev Team
 * @package		PyroCMS\Core\Plugins
 */
class Plugin_format extends Plugin
{

	/**
	 * Markdown
	 *
	 * Takes content and formats it with the Markdown Library.
	 * 
	 * Usage:
	 * {{ format:markdown }}
	 *   Formatted **text**
	 * {{ /format:markdown }}
	 * 
	 * Outputs: <p>Formatted <strong>text</strong></p>
	 * 
	 * @return string The HTML generated by the Markdown Library.
	 */
	public function markdown()
	{
		$this->load->helper('markdown');

		$content = $this->attribute('content', $this->content());

		return parse_markdown(trim($content, "\n"));
	}
	
	
	/**
	 * Textile
	 *
	 * Takes content and formats it with the Textile Library.
	 * 
	 * Usage:
	 * {{ format:textile }}
	 *   Formatted _text_
	 * {{ /format:textile }}
	 * 
	 * Outputs: <p>Formatted <em>text</em></p>
	 * 
	 * @return string The HTML generated by the Textile Library.
	 */
	public function textile()
	{
		$this->load->library('textile');

		$content = $this->attribute('content', $this->content());

		return $this->textile->TextileThis(trim($content, "\n"));
	}


}