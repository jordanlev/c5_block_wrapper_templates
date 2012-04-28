<?php defined('C5_EXECUTE') or die(_("Access Denied."));

class BlockWrapperTemplateArea extends Area {
	
	private $blockWrapperTemplate = null; //relative path+filename (relative to theme directory)
	
	/**
	 * Pass in the path+filename of your template (relative to the theme directory).
	 * If you want to pass in a full path+filename (*not* relative to the theme directory),
	 * pass in FALSE for the 2nd arg. (Note that we need a file path on the server, not a URL relative to the domain name.)
	 * Note that calling this will override any other blockWrapperStart / blockWrapperEnd markup you've set on the area.
	 */
	public function setBlockWrapperTemplate($path, $isRelativeToThemeDir = true) {
		if ($isRelativeToThemeDir) {
			$path = View::getInstance()->getThemeDirectory() . '/' . $path;
		}
		
		$this->blockWrapperTemplate = $path;
	}
	
	
	function display(&$c, $alternateBlockArray = null) {
		if (!is_null($this->blockWrapperTemplate) && file_exists($this->blockWrapperTemplate)) {
			//Hacky approach:
			// 1) Surround each block with a token wrapper that we can identify later.
			//    Make sure the Block ID is in the token somewhere for later use.
			// 2) Display the area but catch output in a buffer.
			// 3) Find each block's output in the buffer by looking for the token wrappers.
			// 4) Send each block's output to the user's template file.
			//    Also send the block object (as determined by the Block ID that's in the wrapper token)
			//    and some other info that might be useful to the template.
			// 5) Replace each block's output in the buffer with the processed template.
			// 6) Output the modified buffer to the browser.
			
			//Set up wrapper token
			$blockIDTokenSplitter = ':';
			$blockIDToken = '%4$s' . $blockIDTokenSplitter . '%1$s'; // as per Area::setBlockWrapperStart/End(), '%4$s' is the block position in the area, and '%1$s' is the block ID
			$wrapperFormatStart = "<!-- BlockWrapperTemplateArea START [%s] -->"; 
			$wrapperFormatEnd = "<!-- BlockWrapperTemplateArea END [%s] -->";
			$this->setBlockWrapperStart(sprintf($wrapperFormatStart, $blockIDToken), true);
			$this->setBlockWrapperEnd(sprintf($wrapperFormatEnd, $blockIDToken), true);
			
			//"Display" the area (it will have the wrapper tokens around each block)
			ob_start();
			$returnValue = parent::display(&$c, $alternateBlockArray = null);
			$buffer = ob_get_clean();
			
			//Pull out each block's content
			$matches = array();
			$pattern = preg_quote($wrapperFormatStart, '/'); //escape literal characters (they'll confuse the regex)
			$pattern = str_replace(preg_quote('%s', '/'), '%s', $pattern); //...except the sprintf token -- unescape that (so we can replace it with the regex match parens in the next step)
			$pattern = '/' . sprintf($pattern, '(.*?)') . '/'; //Don't forget the "?" to make the match greedy!
			preg_match_all($pattern, $buffer, $matches);
			if (!empty($matches[0])) {
				$blockIdentifiers = $matches[1];
				
				//Get block count (so we can send it to the template -- we don't need it ourselves)
				$totalBlocks = $this->getTotalBlocksInArea(); //Do not pass in $c because we already called display(). 
				
				//Templatize each block's output...
				foreach ($blockIdentifiers as $blockIdentifier) {
					//Extract this block's output from buffer
					$wrapperTokenStart = sprintf($wrapperFormatStart, $blockIdentifier);
					$wrapperTokenEnd = sprintf($wrapperFormatEnd, $blockIdentifier);
					$wrapperTokenStartLength = strlen($wrapperTokenStart);
					$wrapperTokenEndLength = strlen($wrapperTokenEnd);
					$wrapperTokenStartEndsAt = strpos($buffer, $wrapperTokenStart) + $wrapperTokenStartLength;
					$wrapperTokenEndStartsAt = strpos($buffer, $wrapperTokenEnd, $wrapperTokenStartEndsAt);
					$innerContent = substr($buffer, $wrapperTokenStartEndsAt, ($wrapperTokenEndStartsAt - $wrapperTokenStartEndsAt));

					//Extract block position and block id from wrapper token in buffer
					list($bPosition, $bID) = explode($blockIDTokenSplitter, $blockIdentifier);
					$block = Block::getByID($bID);
					
					//Get block name (so we can send it to the template -- we don't need it ourselves)
					$bName = ($block->getBlockTypeHandle() == 'core_stack_display') ? Stack::getByID($block->getInstance()->stID)->getStackName() : $block->getBlockName();
					
					//Get templatized output
					$filename = $this->blockWrapperTemplate;
					$args = array(
						'innerContent' => $innerContent,
						'bName' => $bName,
						'position' => $bPosition,
						'totalBlocks' => $totalBlocks,
						'block' => $block, //yes, this sets a reference to the block object (not a copy)
					);
					$templatedBlockOutput = $this->getTemplatedBlockOutput($filename, $args);
					
					//Replace block's output in the buffer with the templatized output
					$before = substr($buffer, 0, ($wrapperTokenStartEndsAt - $wrapperTokenStartLength));
					$after = substr($buffer, ($wrapperTokenEndStartsAt + $wrapperTokenEndLength));
					$buffer = $before . $templatedBlockOutput . $after;
				}
			}
			
			echo $buffer;
			return $returnValue;
		} else { //no template, just do the normal thing...
			return parent::display(&$c, $alternateBlockArray = null);
		}
	}
	
	/**
	 * Private helper function for display()
	 * This is in a separate function so we can isolate the extract($args) variables sent to the template.
	 */
	private function getTemplatedBlockOutput($file, $args = array()) {
		extract($args);
		ob_start();
		include($file);
		return ob_get_clean();
	}
}