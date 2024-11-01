<?php 
if (!defined('ABSPATH')) exit;

$postType = new tfrCustomPostController();
$currentValues = get_post_meta($object->ID, TFR_META_KEY, true);
if (empty($currentValues)): ?>
	<p>Het lijkt er op dat er (nog) geen gegevens zijn ingeladen.. </p>
<?php else: ?>
	<form action="" method="POST">
		<div id="tfr-form-wrapper">
			<div class="row">
				<?php
				foreach ($currentValues as $key => $name) {
					$postType->printMetaToInput($name, array($key), false);
				}
				?>
			</div>
		</div>
	</form>

<script type="text/javascript">
	jQuery(document).on("ready", function(){

	});
</script>

<?php endif; ?>
