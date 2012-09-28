<?php defined('C5_EXECUTE') or die("Access Denied.");

/**
 * Override the display() method to allow for "Block Wrapper Templates"
 * (mini-templates that will be applied to every block in an area).
 * 
 * Note that we're also removing the tokenized blockWrapperStart/blockWrapperEnd
 * functionality, because that can be handled much more simply and elegantly
 * via the new blockWrapperTemplate functionality.
 * 
 * Search this file for "BWT_MOD" to find all of the code changes versus core Area class.
 *  (note that the original code was copied from Concrete5.6.0.2)
 * 
 * FUTURE NOTE: If this functionality ever gets merged into the core system, you'll also
 *  want to remove the following 2 member variables and 1 method from the core Area class:
 *   public $enclosingStartHasReplacements = false;
 *   public $enclosingEndHasReplacements = false;
 *   protected function outputBlockWrapper($isStart, &$block, $blockPositionInArea) { ... }
 */

class Area extends Concrete5_Model_Area {
	
	//BWT_MOD: Added this new member variable...
	private $blockWrapperTemplatePath = null; //relative path+filename (relative to theme directory)
	
	//BWT_MOD: Added this new accessor method...
	//Pass in the path/filename (relative to your theme directory) for the wrapper template.
	//For example, 'myblockwrappertemplate.php' if it's in the top-level of your theme dir,
	// or 'bwt/myblockwrappertemplate.php' if it's inside a sub-folder called "bwt" (within the theme dir).
	public function setBlockWrapperTemplate($file) {
		$this->blockWrapperTemplatePath = '';
		
		if (!empty($file)) {
			$path = View::getInstance()->getThemeDirectory() . '/' . $file;
			if (file_exists($path)) {
				$this->blockWrapperTemplatePath = $path;
			}
		}
	}
	
	/**
	 * displays the Area in the page
	 * ex: $a = new Area('Main'); $a->display($c);
	 * @param Page|Collection $c
	 * @param Block[] $alternateBlockArray optional array of blocks to render instead of default behavior
	 * @return void
	 */
	function display(&$c, $alternateBlockArray = null) {

		if(!intval($c->cID)){
			//Invalid Collection
			return false;
		}
		
		if ($this->arIsGlobal) {
			$stack = Stack::getByName($this->arHandle);
		}		
		$currentPage = Page::getCurrentPage();
		$ourArea = self::getOrCreate($c, $this->arHandle, $this->arIsGlobal);
		if (count($this->customTemplateArray) > 0) {
			$ourArea->customTemplateArray = $this->customTemplateArray;
		}
		if (count($this->attributes) > 0) {
			$ourArea->attributes = $this->attributes;
		}
		if ($this->maximumBlocks > -1) {
			$ourArea->maximumBlocks = $this->maximumBlocks;
		}
		$ap = new Permissions($ourArea);
		if (!$ap->canViewArea()) {
			return false;
		}
		
		$blocksToDisplay = ($alternateBlockArray) ? $alternateBlockArray : $ourArea->getAreaBlocksArray($c, $ap);
		$this->totalBlocks = $ourArea->getTotalBlocksInArea();
		$u = new User();
		
		$bv = new BlockView();
		
		// now, we iterate through these block groups (which are actually arrays of block objects), and display them on the page
		
		if ($this->showControls && $c->isEditMode() && $ap->canViewAreaControls()) {
			$bv->renderElement('block_area_header', array('a' => $ourArea));	
		}

		$bv->renderElement('block_area_header_view', array('a' => $ourArea));	

		//display layouts tied to this area 
		//Might need to move this to a better position  
		$areaLayouts = $this->getAreaLayouts($c);
		if(is_array($areaLayouts) && count($areaLayouts)){ 
			foreach($areaLayouts as $layout){
				$layout->display($c,$this);  
			}
			if($this->showControls && ($c->isArrangeMode() || $c->isEditMode())) {
				echo '<div class="ccm-layouts-block-arrange-placeholder ccm-block-arrange"></div>';
			}
		}
		
		$blockPositionInArea = 1; //for blockWrapper output
		
		foreach ($blocksToDisplay as $b) {
			$includeEditStrip = false;
			$bv = new BlockView();
			$bv->setAreaObject($ourArea); 
			
			// this is useful for rendering areas from one page
			// onto the next and including interactive elements
			if ($currentPage->getCollectionID() != $c->getCollectionID()) {
				$b->setBlockActionCollectionID($c->getCollectionID());
			}
			if ($this->arIsGlobal && is_object($stack)) {
				$b->setBlockActionCollectionID($stack->getCollectionID());
			}
			$p = new Permissions($b);

			if ($c->isEditMode() && $this->showControls && $p->canViewEditInterface()) {
				$includeEditStrip = true;
			}

			if ($p->canViewBlock()) {
				if (!$c->isEditMode()) {
					echo $this->enclosingStart; //BWT_MOD: changed from: $this->outputBlockWrapper(true, $b, $blockPositionInArea);
				}
				if ($includeEditStrip) {
					$bv->renderElement('block_controls', array(
						'a' => $ourArea,
						'b' => $b,
						'p' => $p
					));
					$bv->renderElement('block_header', array(
						'a' => $ourArea,
						'b' => $b,
						'p' => $p
					));
				}

				$this->renderBlock($b, $bv, $c, $blockPositionInArea); //BWT_MOD: changed from: $bv->render($b);
				if ($includeEditStrip) {
					$bv->renderElement('block_footer');
				}
				if (!$c->isEditMode()) {
					$this->enclosingEnd; //BWT_MOD: changed from: $this->outputBlockWrapper(false, $b, $blockPositionInArea);
				}
			}
			
			$blockPositionInArea++;
		}

		$bv->renderElement('block_area_footer_view', array('a' => $ourArea));	

		if ($this->showControls && $c->isEditMode() && $ap->canViewAreaControls()) {
			$bv->renderElement('block_area_footer', array('a' => $ourArea));	
		}
	}
	
	//BWT_MOD: Added this new method...
	protected function renderBlock(&$b, &$bv, &$c, $bPosition) {
		if (empty($this->blockWrapperTemplatePath)) {
			$bv->render($b);
		} else {
			//get the raw block output
			ob_start();
			$bv->render($b);
			$innerContent = ob_get_clean();

			//set some handy variables for the template to use
			if ($b->getBlockTypeHandle() == 'core_stack_display') {
				$stack = Stack::getByID($b->getInstance()->stID);
				$bName = $stack->getStackName();
			} else {
				$stack = null;
				$bName = $b->getBlockName();
			}
			$totalBlocks = $this->totalBlocks; //useful for determining if the current block is last in the area (if bPosition == totalBlocks)

			ob_start();
			include($this->blockWrapperTemplatePath);
			echo ob_get_clean();
		}
	}
	
		
	/** 
	 * Specify HTML to automatically print before blocks contained within the area
	 * @param string $html
	 * @return void
	 */
	function setBlockWrapperStart($html) { //BWT_MOD: changed from: function setBlockWrapperStart($html, $hasReplacements = false) {
		$this->enclosingStart = $html;
		//$this->enclosingStartHasReplacements = $hasReplacements; //BWT_MOD: commented this line out
	}
	
	/** 
	 * Set HTML that automatically prints after any blocks contained within the area
	 * @param string $html
	 * @return void
	 */
	function setBlockWrapperEnd($html) { //BWT_MOD: changed from: function setBlockWrapperEnd($html, $hasReplacements = false) {
		$this->enclosingEnd = $html;
		//$this->enclosingEndHasReplacements = $hasReplacements; //BWT_MOD: commented this line out
	}
}
