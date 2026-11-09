(function($){
    'use strict';

    function getRows($wrap){
        return $wrap.find('.repeater-lite__rows > .repeater-lite__row');
    }

    function reindex($wrap){
        var fieldName = $wrap.data('field-name');
        getRows($wrap).each(function(index){
            $(this).find('[name]').each(function(){
                var name = $(this).attr('name');
                if (!name) return;
                var escaped = String(fieldName).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                var rx = new RegExp('^' + escaped + '\\[[^\\]]+\\]');
                $(this).attr('name', name.replace(rx, fieldName + '[' + index + ']'));
            });
        });
    }

    function updateButtons($wrap){
        var count = getRows($wrap).length;
        var min = parseInt($wrap.data('min-rows'), 10) || 0;
        var max = parseInt($wrap.data('max-rows'), 10) || 0;
        $wrap.find('.repeater-lite__add').prop('disabled', max > 0 && count >= max);
        $wrap.find('.repeater-lite__remove').prop('disabled', count <= min);
    }

    function addRow($wrap){
        var max = parseInt($wrap.data('max-rows'), 10) || 0;
        if (max > 0 && getRows($wrap).length >= max) return;

        var template = $wrap.find('.repeater-lite__template').html();
        var index = getRows($wrap).length;
        template = template.replace(/__INDEX__/g, index);
        $wrap.find('.repeater-lite__rows').append(template);
        reindex($wrap);
        updateButtons($wrap);
    }

    $(document).on('click', '.repeater-lite__add', function(e){
        e.preventDefault();
        addRow($(this).closest('.repeater-lite'));
    });

    $(document).on('click', '.repeater-lite__remove', function(e){
        e.preventDefault();
        var $wrap = $(this).closest('.repeater-lite');
        var min = parseInt($wrap.data('min-rows'), 10) || 0;
        if (getRows($wrap).length <= min) return;
        $(this).closest('.repeater-lite__row').remove();
        reindex($wrap);
        updateButtons($wrap);
    });

    $(document).on('click', '.repeater-lite__image-select', function(e){
        e.preventDefault();
        var $field = $(this).closest('.repeater-lite__image-field');
        var frame = wp.media({
            title: 'Select Image',
            button: { text: 'Use Image' },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $field.find('.repeater-lite__image-id').val(attachment.id);
            var src = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
            $field.find('.repeater-lite__image-preview').html('<img src="' + src + '" alt="">');
        });

        frame.open();
    });

    $(document).on('click', '.repeater-lite__image-remove', function(e){
        e.preventDefault();
        var $field = $(this).closest('.repeater-lite__image-field');
        $field.find('.repeater-lite__image-id').val('');
        $field.find('.repeater-lite__image-preview').empty();
    });

    $(function(){
        $('.repeater-lite').each(function(){
            var $wrap = $(this);
            var min = parseInt($wrap.data('min-rows'), 10) || 0;
            while (getRows($wrap).length < min) addRow($wrap);
            updateButtons($wrap);
        });
    });
})(jQuery);
