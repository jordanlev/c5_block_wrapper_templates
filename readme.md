# Block Wrapper Templates

This is a file which modifies the core 'Area' functionality by adding a new "setBlockWrapperTemplate" method.

To install it on your website, move the included `area.php` file to your site's top-level `models` directory (*not* `concrete/models`).

Once the `area.php` file has been placed in your site's top-level `models` directory, you can utilize this new functionality by calling the "setBlockWrapperTemplate()" function when you place areas in your theme templates (aka page type templates). For example, you could put this in your template:

	<div id="sidebar">
		<?php
		$a = new Area('Sidebar');
		$a->setBlockWrapperTemplate('elements/orange_sidebar_block_wrapper.php');
		$a->display($c);
		?>
	</div>

Then, create a new file called `orange_sidebar_block_wrapper.php` inside your theme's `elements` folder. Edit the new `orange_sidebar_block_wrapper.php` file and put in something like the following:

	<div style="border: 2px solid orange;">
	  <?php echo $innerContent; ?>
	</div>

Now every block in that sidebar area gets an orange border around it.

But wait, there's more! The above example doesn't do anything that that the existing blockWrapperStart() and blockWrapperEnd() functions couldn't already do. However, with the block wrapper templates, you can do many more things.

For example, if you want to apply a style only to the first block in the area:

	<div <?php if ($bPosition == 1) { echo 'style="border-top: 2px solid orange;"' } ?>>
		<?php echo $innerContent; ?>
	</div>

or only the last block in the area:

	<div <?php if ($bPosition == $totalBlocks) { echo 'style="border-bottom: 2px solid orange;"' } ?>>
		<?php echo $innerContent; ?>
	</div>

or alternate the background color of each block ("zebra striping"):

	<div class="<?php echo ($bPosition % 2) ? 'odd' : 'even'; ?>">
		<?php echo $innerContent; ?>
	</div>

or display a title for every block (titles are taken from the Block Name field in the Custom Templates popup):

	<div class="sidebar-block-wrapper">
		<h2><?php echo $bName; ?></h2>
		<?php echo $innerContent; ?>
	</div>

BUT WAIT, THERE'S EVEN MORE!!!

If you want to display the area as an accordion, with each block being its own panel, put this in your page type template:

	<div id="sidebar" class="accordion">
		<?php
		$a = new Area('Sidebar');
		if (!$c->isEditMode()) {
			$a->setBlockWrapperTemplate('elements/accordion_block_wrapper.php');
		}
		$a->display($c);
		?>
	</div>
	<script>
		$(document).ready(function() {
			var allPanels = $('.accordion > .panel').hide();
			$('.accordion > .title > a').click(function() {
				allPanels.slideUp();
				$(this).parent().next().slideDown();
				return false;
			});
		});
	</script>

...then create a new `accordion_block_wrapper.php` file in your theme's `elements` directory, with the following contents:

	<div class="title"><a href="#"><?php echo $bName; ?></a></div>
	<div class="panel">
		<?php echo $innerContent; ?>
	</div>

The possibilities are endless!

# Available Data in Templates
Inside your block wrapper templates, the following variables are available:

* `$innerContent` - the html contents of the block. This should always be echo'd out in your template.
* `$bName` - block name (can be set by clicking on the block in edit mode and choosing "Custom Template" from the popup menu)
* `$bPosition` - the order number of this block in its area (first block is 1, second is 2, etc.)
* `$totalBlocks` - the total number of blocks in the area
* `$b` - the block object
* `$stack` - stack object (if the block is a stack)
* `$c` - the collection object (same as in theme templates -- e.g. `if ($c->isEditMode()) { ... }`)
* `$bv` - the block_view object (same as `$this` in theme templates -- e.g. `$bv->getThemePath()`)
