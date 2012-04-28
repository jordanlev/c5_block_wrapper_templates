c5_block_wrapper_templates
==========================

Adds &quot;block wrapper template&quot; functionality to Concrete5.5.1+, so you can use php logic to conditionally alter output or styles around every block in an area (regardless of which kind of block it is). Block wrapper templates are like miniature page type templates, but instead of containing the html markup that surrounds a page, they only contain the html markup that surrounds each block. Why would you want to do this? Some example use cases:
 * Add "zebra striping" to an area, so each block has an alternating background color
 * Add a "shadow box" around each block with a title displayed above it
 * Output an `<hr>` tag in between each block (after every block except the last one in the area)
 * ...the possibilities are endless (translation: I can't think of any more right now)!


## Installation
Drop the `block_wrapper_template_area.php` file into your site's top-level `models` directory


## Usage
1. Create the block wrapper template file in your theme's `elements` directory.
2. Construct your template using a few variables that are made available to it:
    * `$innerContent`: The block's html output. This must always be in the template once (and only once).
    * `$name`: The name of the block (or stack), as per the 'Block Name' field you see when you click on the block in edit mode and choose "Custom Template" (or the Stack Name you set in the dashboard Stacks page). This is useful for titles/headings above blocks that don't have separate fields for them (e.g. form, page list, etc.).
    * `$position`: The block's position in the area -- first block is 1, second block is 2, and so on. Use this to determine if a block is the first in the area (by checking `if ($position == 1)`), or to assign unique css class names to different blocks (e.g. `<div class="my-block-<?php echo $position; ?>">`), or to enable alternating css classes for zebra striping (`<div class="<?php echo ($position % 2) ? 'odd' : 'even'; ?>">`).
    * `$totalBlocks`: The total number of blocks in the area. Probably only useful for determining if a block is the *last* one in the area, by checking `if ($position == $totalBlocks)`.
    * `$block`: The block object. Use this for everything else you might want to do. Note that for some things, you will need the "block instance", which you can get by calling `$block->getInstance()`. *I don't understand all the ins and outs of this, but it seems that if you want general block meta-information you make calls against the block object, whereas if you want to access methods or properties of the block controller you make calls against the block instance.*
    * `$stack`: For most blocks this is null, but if the "block" being outputted is actually a stack, then this is the stack object. Note that because C5 treats an entire stack as a single block when displaying them in areas like this, the block wrapper template will go around the entire stack and not the individual blocks within the stack (which might be a good or bad thing, depending on how you look at it).
3. Declare your block wrapper templates on the desired area(s) in your theme templates, like so:

        <?php
        Loader::model('block_wrapper_template_area');
        $a = new BlockWrapperTemplateArea('Sidebar');
        $a->setBlockWrapperTemplate('elements/sidebar_block_wrapper.php');
        $a->display($c);
        ?>

    Note that the Loader::model line should only appear once in each template file, even if you'll have more than one area using the block wrapper templates. For example, if you wanted block wrapper templates around the 'Main' AND 'Sidebar' areas, you'd do something like this:

        <?php Loader::model('block_wrapper_template_area'); ?>
    
        <div class="main">
            <?php
            $a = new BlockWrapperTemplateArea('Main');
            $a->setBlockWrapperTemplate('elements/main_block_wrapper.php');
            $a->display($c);
            ?>
        </div>
    
        <div class="sidebar">
            <?php
            $a = new BlockWrapperTemplateArea('Sidebar');
            $a->setBlockWrapperTemplate('elements/sidebar_block_wrapper.php');
            $a->display($c);
            ?>
        </div>

## Example 1: Zebra Striping
*Make sure you've installed the `block_wrapper_template_area.php` file into your site's top-level `models` directory!*

1. Create a file called `zebra_stripe_block_wrapper.php` in your theme's `elements` directory.
2. Paste this code into that new file:

        <?php defined('C5_EXECUTE') or die(_("Access Denied."));
        
        $isOdd = (bool)($position % 2);
        $cssClass = $isOdd ? 'odd-block' : 'even-block';
        ?>

        <div class="<?php echo $cssClass; ?>">
        	<?php echo $innerContent; ?>
        </div>
3. Change the 'Sidebar' area code in your theme templates to this:

        <div class="zebra-stripe-area">
            <?php
            Loader::model('block_wrapper_template_area');
            $a = new BlockWrapperTemplateArea('Sidebar');
            $a->setBlockWrapperTemplate('elements/zebra_stripe_block_wrapper.php');
            $a->display($c);
            ?>
        </div>
4. Add some styling rules to your theme's CSS, for example:

        .zebra-stripe-area {
            background-color: #FFF;
        }
        .zebra-stripe-area .even {
            background-color: #999;
        }

## Example 2: Line Between Blocks
*Make sure you've installed the `block_wrapper_template_area.php` file into your site's top-level `models` directory!*

1. Create a file called `inbetween_line_block_wrapper.php` in your theme's `elements` directory.
2. Paste this code into that new file:

        <?php defined('C5_EXECUTE') or die(_("Access Denied."));
        
        echo $innerContent;
        
        //Output HR for all blocks EXCEPT the last one in the area
        if ($position < $totalBlocks) {
            echo '<hr />';
        }
3. Change the 'Main' area code in your theme template to this:

        <?php
        Loader::model('block_wrapper_template_area');
        $a = new BlockWrapperTemplateArea('Main');
        $a->setBlockWrapperTemplate('elements/inbetween_line_block_wrapper.php');
        $a->display($c);
        ?>

## Example 3: Titles and Boxes Around Every Block
*Make sure you've installed the `block_wrapper_template_area.php` file into your site's top-level `models` directory!*

This example uses the block name (which is set by clicking on a block while in edit mode and choosing "Custom Template" from the popup menu, then entering a name in the 'Block Name' field) to display a title above and a border around each block in the sidebar. If you know there will only be "content" blocks in the sidebar, you don't need to do this because you can just set an `<h2>` style and have your users enter that in the content block. And if you don't want snazzy borders around each block, you don't need to do this either because you could just have users add a content block above every other block that has an `<h2>` in it. But if you want other types of blocks in the sidebar to have titles and also want to put borders around each one, you need something like this.

1. Create a file called `title_and_border_block_wrapper.php` in your theme's `elements` directory.
2. Paste this code into that new file:

        <?php defined('C5_EXECUTE') or die(_("Access Denied.")); ?>

        <div class="sidebar-block">
        	<?php
        	if (!empty($name)) {
        		echo '<h2>' . htmlentities($name) . '</h2>';
        	}
        	echo $innerContent;
        	?>
        </div><!-- .sidebar-block -->
3. Change the 'Sidebar' area code in your theme template to this:

        <?php
        Loader::model('block_wrapper_template_area');
        $a = new BlockWrapperTemplateArea('Sidebar');
        $a->setBlockWrapperTemplate('elements/title_and_border_block_wrapper.php');
        $a->display($c);
        ?>
4. Add some styling rules to your theme's CSS, for example:

        .sidebar-block {
            border: 2px solid #666;
            padding: 5px;
            margin: 5px;
        }
        .sidebar-block h2 {
            font-family: Georgia, sans-serif;
            font-size: 20px;
            color: red;
            padding: 10px;
        }
        

## Potential Issues
* I'm not sure what the performance penalties are of this. It is doing a bit of extra processing for each block outputted... and it is also doing a lot of string manipulation on the outputted markup of each area, so if you have an area with a LOT of blocks in it, I imagine this might become problematic. But I have no idea where that threshold between "not noticeable" and "bogging down the system" is.
* I'm not sure if/how this interacts with caching.
* I'm not sure how this works with Area Layouts (shouldn't break anything, but who knows... layouts are so kludgy to begin with).
