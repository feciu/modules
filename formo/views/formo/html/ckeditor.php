<?php echo $open; ?>
	<label>
		<?php echo $label; ?>
		<span class="field">
			<?php if ($field->get('editable') === TRUE): ?>
				<?php echo $field->add_class('input')->attr('rows', 10)->render(); ?>
			<?php else: ?>
				<span><?php echo $field->val(); ?></span>
			<?php endif; ?>
		</span>
	</label>
	<?php echo $message; ?>
<?php echo $close; ?>
<?php


 $editor = Editor::factory('ckeditor');
        $s = $editor->js();
        $editor->width = '600';
        $editor->height = '200';
        $editor->fieldname = 'form['.$field->name.']';
        $editor->render(TRUE, FALSE);
?>
